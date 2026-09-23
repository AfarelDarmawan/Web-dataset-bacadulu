<?php

namespace App\Services\Sync;

use App\Models\DataConnector;
use App\Models\DataSyncRow;
use App\Models\DataSyncRun;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BpsConnectorService
{
    public function __construct(private readonly BpsPayloadTransformer $transformer)
    {
    }

    public function run(DataConnector $connector, ?User $actor = null, string $trigger = 'manual'): DataSyncRun
    {
        $connector->loadMissing(['dataset', 'variable']);
        if ($connector->type !== 'bps') {
            throw new DataSyncException('Jenis konektor belum didukung.', 'connector_type_unsupported');
        }
        if (! $connector->isActive()) {
            throw new DataSyncException('Aktifkan konektor sebelum menjalankan sinkronisasi.', 'connector_paused');
        }
        if ($connector->variable->dataset_id !== $connector->dataset_id || $connector->dataset->data_scope !== 'regional') {
            throw new DataSyncException('Tujuan konektor tidak lagi cocok dengan dataset Statistik wilayah & pemerintah.', 'connector_target_invalid');
        }
        if (! $connector->variable->is_active) {
            throw new DataSyncException('Aktifkan variabel tujuan sebelum menjalankan sinkronisasi.', 'connector_variable_inactive');
        }
        if (! config('bacadulu.bps.mock') && (! config('bacadulu.bps.enabled') || blank(config('bacadulu.bps.api_key')))) {
            throw new DataSyncException('WebAPI BPS belum aktif. Isi BPS_API_KEY dan aktifkan BACADULU_BPS_ENABLED.', 'bps_not_configured');
        }

        $run = DB::transaction(function () use ($connector, $actor, $trigger): DataSyncRun {
            $locked = DataConnector::query()->lockForUpdate()->findOrFail($connector->id);
            if ($locked->runs()->whereIn('status', ['queued', 'processing', 'review'])->exists()) {
                throw new DataSyncException('Konektor masih memiliki sinkronisasi yang berjalan atau menunggu review.', 'sync_already_open');
            }

            $locked->update(['last_started_at' => now()]);

            return $locked->runs()->create([
                'triggered_by' => $actor?->id,
                'trigger' => in_array($trigger, ['manual', 'scheduled'], true) ? $trigger : 'manual',
                'status' => 'queued',
            ]);
        });

        try {
            $run->update(['status' => 'processing', 'started_at' => now()]);
            [$payload, $rawBody] = $this->fetchPayload($connector);
            $transformed = $this->transformer->transform($payload, $connector);
            $previousRun = DataSyncRun::query()
                ->where('data_connector_id', $connector->id)
                ->where('id', '!=', $run->id)
                ->whereIn('status', ['review', 'applied'])
                ->latest('id')
                ->first();
            $previousSchemaChecksum = $previousRun?->metadata['source_schema_checksum'] ?? null;
            $schemaChanged = filled($previousSchemaChecksum)
                && ! hash_equals((string) $previousSchemaChecksum, $transformed['source_schema_checksum']);

            $now = now();
            $rows = collect($transformed['rows'])->map(function (array $row) use ($run, $connector, $now, $schemaChanged): array {
                if ($schemaChanged && $row['validation_status'] !== 'invalid') {
                    $row['validation_status'] = 'warning';
                    $issues = $row['validation_issues'] ?? [];
                    $issues[] = 'Metadata variabel BPS berubah sejak sinkronisasi sebelumnya.';
                    $row['validation_issues'] = $issues;
                }

                return $row + [
                    'data_sync_run_id' => $run->id,
                    'dataset_variable_id' => $connector->dataset_variable_id,
                    'status' => 'proposed',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->map(function (array $row): array {
                $row['validation_issues'] = $row['validation_issues'] === null
                    ? null
                    : json_encode($row['validation_issues'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $row['source_payload'] = json_encode($row['source_payload'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

                return $row;
            });

            DB::transaction(function () use ($rows, $run, $connector, $transformed, $rawBody, $schemaChanged, $now): void {
                $rows->chunk(500)->each(fn ($chunk) => DataSyncRow::insert($chunk->all()));
                $run->update([
                    'status' => 'review',
                    'rows_count' => $rows->count(),
                    'valid_rows_count' => $rows->where('validation_status', 'valid')->count(),
                    'warning_rows_count' => $rows->where('validation_status', 'warning')->count(),
                    'invalid_rows_count' => $rows->where('validation_status', 'invalid')->count(),
                    'created_rows_count' => $rows->where('proposed_action', 'create')->count(),
                    'updated_rows_count' => $rows->where('proposed_action', 'update')->count(),
                    'unchanged_rows_count' => $rows->where('proposed_action', 'unchanged')->count(),
                    'response_checksum' => hash('sha256', $rawBody),
                    'metadata' => [
                        'source' => 'BPS WebAPI',
                        'domain' => $connector->config['domain'] ?? null,
                        'variable_id' => $connector->config['variable_id'] ?? null,
                        'source_schema' => $transformed['source_schema'],
                        'source_schema_checksum' => $transformed['source_schema_checksum'],
                        'schema_changed' => $schemaChanged,
                        'mock' => (bool) config('bacadulu.bps.mock'),
                    ],
                    'fetched_at' => $now,
                    'completed_at' => $now,
                ]);
                $connector->update([
                    'last_succeeded_at' => $now,
                    'next_sync_at' => $connector->nextScheduledAt($now),
                    'consecutive_failures' => 0,
                    'last_error' => null,
                ]);
            });

            return $run->fresh(['connector', 'rows']);
        } catch (Throwable $exception) {
            $safe = $exception instanceof DataSyncException
                ? $exception
                : new DataSyncException('Sinkronisasi gagal diproses. Periksa koneksi dan konfigurasi konektor.', 'sync_runtime_error');
            $this->markFailed($run, $connector, $safe);
            if (! ($exception instanceof DataSyncException)) {
                Log::error('BPS connector sync failed', [
                    'connector_id' => $connector->id,
                    'run_id' => $run->id,
                    'exception' => $exception::class,
                ]);
            }

            throw $safe;
        }
    }

    private function fetchPayload(DataConnector $connector): array
    {
        if (config('bacadulu.bps.mock')) {
            $payload = $this->mockPayload($connector);
            $raw = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            return [$payload, $raw];
        }

        $apiKey = (string) config('bacadulu.bps.api_key');
        $config = $connector->config;
        $segments = [
            'model', 'data',
            'lang', $config['language'] ?? 'ind',
            'domain', $config['domain'],
            'var', $config['variable_id'],
            'th', $config['period_ids'],
        ];
        foreach (['derived_variable_id' => 'turvar', 'vertical_variable_id' => 'vervar', 'derived_period_id' => 'turth'] as $key => $segment) {
            if (filled($config[$key] ?? null)) {
                array_push($segments, $segment, $config[$key]);
            }
        }
        array_push($segments, 'key', $apiKey);
        $path = implode('/', array_map(fn ($part): string => rawurlencode((string) $part), $segments));
        $url = rtrim((string) config('bacadulu.bps.endpoint'), '/').'/'.$path;

        try {
            $response = Http::acceptJson()
                ->withOptions(['allow_redirects' => false])
                ->timeout((int) config('bacadulu.bps.timeout_seconds', 25))
                ->retry(2, 250, throw: false)
                ->get($url);
        } catch (ConnectionException) {
            throw new DataSyncException('WebAPI BPS tidak dapat dihubungi. Coba lagi beberapa saat.', 'bps_connection_failed');
        }

        if (! $response->successful()) {
            throw new DataSyncException('WebAPI BPS menolak permintaan dengan status '.$response->status().'.', 'bps_http_error');
        }
        $rawBody = $response->body();
        if (strlen($rawBody) > ((int) config('bacadulu.bps.max_response_kilobytes', 5120) * 1024)) {
            throw new DataSyncException('Respons BPS terlalu besar. Persempit periode atau wilayah konektor.', 'bps_response_too_large');
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new DataSyncException('Respons WebAPI BPS bukan JSON yang valid.', 'bps_invalid_json');
        }
        if (($payload['status'] ?? null) !== 'OK' || ($payload['data-availability'] ?? null) !== 'available') {
            throw new DataSyncException('BPS tidak menyediakan data untuk kombinasi konfigurasi tersebut.', 'bps_unavailable');
        }

        return [$payload, $rawBody];
    }

    private function markFailed(DataSyncRun $run, DataConnector $connector, DataSyncException $exception): void
    {
        $failures = min(20, ((int) $connector->consecutive_failures) + 1);
        $retryAt = $connector->schedule === 'manual'
            ? null
            : now()->addHours(min(24, 2 ** min(4, $failures)));
        $run->update([
            'status' => 'failed',
            'error_code' => $exception->errorCode,
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
        ]);
        $connector->update([
            'consecutive_failures' => $failures,
            'last_error' => $exception->getMessage(),
            'next_sync_at' => $retryAt,
        ]);
    }

    private function mockPayload(DataConnector $connector): array
    {
        $variableId = (int) ($connector->config['variable_id'] ?? 1000);
        $derivedVariableId = (int) ($connector->config['derived_variable_id'] ?? 0);
        $derivedPeriodId = (int) ($connector->config['derived_period_id'] ?? 0);
        $selectedGeographyId = $connector->config['vertical_variable_id'] ?? null;
        $year = (int) now()->format('Y');
        $geographies = $selectedGeographyId !== null && $selectedGeographyId !== ''
            ? [['val' => (int) $selectedGeographyId, 'label' => 'Wilayah simulasi']]
            : [
                ['val' => 3100, 'label' => 'DKI Jakarta'],
                ['val' => 3200, 'label' => 'Jawa Barat'],
                ['val' => 3300, 'label' => 'Jawa Tengah'],
            ];
        $periods = [
            ['val' => 1, 'label' => (string) ($year - 1)],
            ['val' => 2, 'label' => (string) $year],
        ];
        $content = [];
        foreach ($geographies as $geographyIndex => $geography) {
            foreach ($periods as $periodIndex => $period) {
                $key = $geography['val'].$variableId.$derivedVariableId.$period['val'].$derivedPeriodId;
                $content[$key] = 1000000 + ($geographyIndex * 125000) + ($periodIndex * 35000);
            }
        }

        return [
            'status' => 'OK',
            'data-availability' => 'available',
            'var' => [[
                'val' => $variableId,
                'label' => $connector->variable->name,
                'unit' => $connector->variable->unit,
                'def' => $connector->variable->definition,
            ]],
            'turvar' => [['val' => $derivedVariableId, 'label' => 'Kategori simulasi']],
            'labelvervar' => 'Provinsi',
            'vervar' => $geographies,
            'tahun' => $periods,
            'turtahun' => [['val' => $derivedPeriodId, 'label' => $derivedPeriodId === 0 ? 'Tahun' : 'Periode simulasi']],
            'datacontent' => $content,
        ];
    }
}
