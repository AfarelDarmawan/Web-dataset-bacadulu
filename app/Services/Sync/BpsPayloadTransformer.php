<?php

namespace App\Services\Sync;

use App\Models\DataConnector;
use App\Models\DatasetObservation;
use Illuminate\Support\Str;

class BpsPayloadTransformer
{
    public function transform(array $payload, DataConnector $connector): array
    {
        $connector->loadMissing(['variable', 'dataset']);
        $config = $connector->config;
        $variableId = (string) ($config['variable_id'] ?? '');
        $variables = $this->items($payload['var'] ?? []);
        $sourceVariable = collect($variables)->first(
            fn (array $item): bool => (string) ($item['val'] ?? '') === $variableId
        ) ?? ($variables[0] ?? ['val' => $variableId, 'label' => $connector->variable->name]);

        $derivedVariables = $this->items($payload['turvar'] ?? []);
        $verticalVariables = $this->items($payload['vervar'] ?? []);
        $periods = $this->items($payload['tahun'] ?? []);
        $derivedPeriods = $this->items($payload['turtahun'] ?? []);

        if ($derivedVariables === []) {
            $derivedVariables = [['val' => 0, 'label' => 'Total']];
        }
        if ($verticalVariables === []) {
            $verticalVariables = [[
                'val' => $config['domain'] ?? '0000',
                'label' => $payload['labelvervar'] ?? 'Indonesia',
            ]];
        }
        if ($derivedPeriods === []) {
            $derivedPeriods = [['val' => 0, 'label' => 'Tahun']];
        }
        if ($periods === []) {
            throw new DataSyncException('Respons BPS tidak memuat periode data.', 'bps_period_missing');
        }

        $derivedVariables = $this->filterSelected($derivedVariables, $config['derived_variable_id'] ?? null, 'turunan variabel');
        if (count($derivedVariables) > 1) {
            throw new DataSyncException(
                'Variabel BPS memiliki beberapa kategori turunan. Isi ID turunan variabel agar setiap konektor hanya memetakan satu seri data.',
                'bps_derived_variable_ambiguous'
            );
        }
        $verticalVariables = $this->filterSelected($verticalVariables, $config['vertical_variable_id'] ?? null, 'wilayah/vertical');
        $derivedPeriods = $this->filterSelected($derivedPeriods, $config['derived_period_id'] ?? null, 'turunan periode');

        $content = is_array($payload['datacontent'] ?? null) ? $payload['datacontent'] : [];
        if ($content === []) {
            throw new DataSyncException('BPS tidak mengembalikan nilai untuk konfigurasi ini.', 'bps_data_empty');
        }

        $existing = DatasetObservation::query()
            ->where('dataset_id', $connector->dataset_id)
            ->where('dataset_variable_id', $connector->dataset_variable_id)
            ->get()
            ->keyBy(fn (DatasetObservation $observation): string => $observation->geography_code.'|'.$observation->period);

        $rows = [];
        $rowIndex = 0;
        $maximumRows = (int) config('bacadulu.bps.max_rows', 20000);
        foreach ($verticalVariables as $geography) {
            foreach ($derivedVariables as $derivedVariable) {
                foreach ($periods as $period) {
                    foreach ($derivedPeriods as $derivedPeriod) {
                        $contentKey = $this->contentKey($geography, $sourceVariable, $derivedVariable, $period, $derivedPeriod);
                        if (! array_key_exists($contentKey, $content)) {
                            continue;
                        }

                        $rowIndex++;
                        if ($rowIndex > $maximumRows) {
                            throw new DataSyncException(
                                "Respons BPS melewati batas {$maximumRows} baris. Persempit wilayah atau periode konektor.",
                                'bps_row_limit'
                            );
                        }

                        $periodLabel = $this->periodLabel($period, $derivedPeriod);
                        $geographyCode = Str::limit((string) ($geography['val'] ?? ''), 100, '');
                        $geographyName = Str::limit(trim((string) ($geography['label'] ?? '')), 255, '');
                        $rawValue = $content[$contentKey];
                        $issues = [];
                        $numericTypes = ['numeric', 'percentage', 'currency', 'index'];
                        $expectsNumeric = in_array($connector->variable->data_type, $numericTypes, true);
                        $valueNumeric = is_numeric($rawValue) ? (string) $rawValue : null;
                        $valueText = $valueNumeric === null && $rawValue !== null ? trim((string) $rawValue) : null;

                        if ($geographyCode === '' || $geographyName === '') {
                            $issues[] = 'Kode atau nama wilayah tidak tersedia.';
                        }
                        if ($periodLabel === '') {
                            $issues[] = 'Label periode tidak tersedia.';
                        }
                        if ($rawValue === null || $rawValue === '') {
                            $issues[] = 'Nilai data kosong.';
                        } elseif ($expectsNumeric && $valueNumeric === null) {
                            $issues[] = 'Sumber mengirim nilai nonnumerik untuk variabel numerik.';
                        } elseif ($valueNumeric !== null && abs((float) $valueNumeric) >= 1_000_000_000_000_000_000) {
                            $issues[] = 'Nilai melampaui kapasitas numerik database.';
                        }

                        $sourceUnit = trim((string) ($sourceVariable['unit'] ?? ''));
                        if ($sourceUnit !== '' && filled($connector->variable->unit)
                            && Str::lower($sourceUnit) !== Str::lower(trim((string) $connector->variable->unit))) {
                            $issues[] = "Satuan BPS ({$sourceUnit}) berbeda dari kamus variabel ({$connector->variable->unit}).";
                        }

                        $existingObservation = $existing->get($geographyCode.'|'.$periodLabel);
                        $proposedAction = $existingObservation
                            ? ($this->sameValue($existingObservation, $valueNumeric, $valueText) ? 'unchanged' : 'update')
                            : 'create';
                        if ($proposedAction === 'update') {
                            $issues[] = 'Nilai berbeda dari observasi yang sudah tersimpan.';
                        }

                        $validationStatus = $issues === [] ? 'valid' : 'warning';
                        if (collect($issues)->contains(fn (string $issue): bool => str_contains($issue, 'tidak tersedia')
                            || str_contains($issue, 'kosong')
                            || str_contains($issue, 'nonnumerik')
                            || str_contains($issue, 'kapasitas'))) {
                            $validationStatus = 'invalid';
                        }

                        $externalKey = implode(':', [
                            'bps',
                            $config['domain'] ?? '0000',
                            $sourceVariable['val'] ?? $variableId,
                            $derivedVariable['val'] ?? 0,
                            $geography['val'] ?? '',
                            $period['val'] ?? '',
                            $derivedPeriod['val'] ?? 0,
                        ]);

                        $rows[] = [
                            'row_index' => $rowIndex,
                            'external_key' => Str::limit($externalKey, 191, ''),
                            'geography_code' => $geographyCode,
                            'geography_name' => $geographyName,
                            'period' => Str::limit($periodLabel, 30, ''),
                            'value_numeric' => $valueNumeric,
                            'value_text' => $valueText,
                            'source_reference' => "BPS WebAPI · domain ".($config['domain'] ?? '0000')." · variabel {$variableId}",
                            'proposed_action' => $proposedAction,
                            'validation_status' => $validationStatus,
                            'validation_issues' => $issues ?: null,
                            'existing_observation_id' => $existingObservation?->id,
                            'source_payload' => [
                                'content_key' => $contentKey,
                                'derived_variable' => $derivedVariable['label'] ?? null,
                                'derived_period' => $derivedPeriod['label'] ?? null,
                            ],
                        ];
                    }
                }
            }
        }

        if ($rows === []) {
            throw new DataSyncException(
                'Nilai BPS tersedia, tetapi tidak cocok dengan kombinasi wilayah dan periode yang dikembalikan.',
                'bps_mapping_empty'
            );
        }

        $sourceSchema = [
            'variable_id' => (string) ($sourceVariable['val'] ?? $variableId),
            'name' => (string) ($sourceVariable['label'] ?? ''),
            'unit' => (string) ($sourceVariable['unit'] ?? ''),
            'definition' => (string) ($sourceVariable['def'] ?? ''),
            'geography_label' => (string) ($payload['labelvervar'] ?? ''),
        ];

        return [
            'rows' => $rows,
            'source_schema' => $sourceSchema,
            'source_schema_checksum' => hash('sha256', json_encode($sourceSchema, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
        ];
    }

    private function items(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        if (array_is_list($value)) {
            return array_values(array_filter($value, 'is_array'));
        }

        return isset($value['val']) ? [$value] : [];
    }

    private function filterSelected(array $items, mixed $selected, string $dimension): array
    {
        if ($selected === null || $selected === '') {
            return $items;
        }

        $filtered = array_values(array_filter(
            $items,
            fn (array $item): bool => (string) ($item['val'] ?? '') === (string) $selected
        ));

        if ($filtered === []) {
            $available = collect($items)->pluck('val')->filter(fn ($value): bool => $value !== null && $value !== '')->take(12)->implode(', ');
            throw new DataSyncException(
                "ID {$dimension} {$selected} tidak ada dalam respons BPS. ID tersedia: {$available}. Periksa pemetaan konektor dan domain BPS.",
                'bps_dimension_missing'
            );
        }

        return $filtered;
    }

    private function contentKey(array ...$parts): string
    {
        return implode('', array_map(fn (array $part): string => (string) ($part['val'] ?? ''), $parts));
    }

    private function periodLabel(array $period, array $derivedPeriod): string
    {
        $year = trim((string) ($period['label'] ?? $period['val'] ?? ''));
        $derived = trim((string) ($derivedPeriod['label'] ?? ''));
        if ($derived === '' || Str::lower($derived) === 'tahun') {
            return $year;
        }

        return trim($year.' · '.$derived);
    }

    private function sameValue(DatasetObservation $observation, ?string $numeric, ?string $text): bool
    {
        if ($numeric !== null) {
            return $observation->value_numeric !== null
                && abs((float) $observation->value_numeric - (float) $numeric) < 0.0000005;
        }

        return $observation->value_numeric === null && (string) $observation->value_text === (string) $text;
    }
}
