<?php

namespace App\Services\Ai;

use App\Models\AiExtractionJob;
use App\Models\AiExtractionRow;
use App\Models\SourceDocument;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DatasetExtractionService
{
    public function __construct(
        private readonly LocalCsvStagingExtractor $csvExtractor,
        private readonly OpenAiDatasetExtractor $openAiExtractor,
        private readonly ExtractionRowValidator $rowValidator,
    ) {}

    public function run(SourceDocument $document, User $user): AiExtractionJob
    {
        $document->loadMissing(['dataset.provider', 'dataset.variables']);
        $absolutePath = Storage::disk($document->disk)->path($document->storage_path);
        $currentHash = is_readable($absolutePath) ? hash_file('sha256', $absolutePath) : false;
        if (! is_string($currentHash) || ! hash_equals($document->sha256, $currentHash)) {
            $document->update(['status' => 'failed']);
            AuditService::record('source_document.integrity_failed', $document, ['dataset_id' => $document->dataset_id]);
            throw new ExtractionException('Integritas dokumen gagal diverifikasi. Unggah ulang sumber sebelum ekstraksi.', 'source_integrity_failed');
        }
        $extractor = in_array($document->extension, ['csv', 'txt'], true) ? 'local_csv' : 'openai';

        if ($extractor === 'openai' && (! config('bacadulu.ai.enabled') || blank(config('bacadulu.ai.api_key')))) {
            throw new ExtractionException('AI belum dikonfigurasi. Tambahkan OPENAI_API_KEY dan aktifkan BACADULU_AI_ENABLED, atau unggah CSV.', 'ai_not_configured');
        }

        $transaction = DB::transaction(function () use ($document, $user, $extractor): array {
            $locked = SourceDocument::query()->lockForUpdate()->findOrFail($document->id);
            $staleBefore = now()->subSeconds((int) config('bacadulu.ai.stale_after_seconds', 300));
            $staleJobs = $locked->extractionJobs()
                ->whereIn('status', ['queued', 'processing'])
                ->where(fn ($query) => $query
                    ->where('started_at', '<=', $staleBefore)
                    ->orWhere(fn ($missingStart) => $missingStart
                        ->whereNull('started_at')
                        ->where('created_at', '<=', $staleBefore)))
                ->lockForUpdate()
                ->get();

            foreach ($staleJobs as $staleJob) {
                $staleJob->update([
                    'status' => 'failed',
                    'error_code' => 'stale_process_recovered',
                    'error_message' => 'Proses sebelumnya berhenti tanpa menyelesaikan respons. Status dipulihkan agar ekstraksi dapat dicoba ulang.',
                    'completed_at' => now(),
                ]);
            }

            $hasOpenJob = $locked->extractionJobs()->whereIn('status', ['queued', 'processing', 'review'])->exists();
            if ($hasOpenJob) {
                throw new ExtractionException('Dokumen ini masih memiliki ekstraksi aktif atau menunggu review.', 'open_job_exists');
            }

            $locked->update(['status' => 'processing']);

            return [
                'job' => $locked->extractionJobs()->create([
                    'dataset_id' => $locked->dataset_id,
                    'requested_by' => $user->id,
                    'status' => 'processing',
                    'extractor' => $extractor,
                    'model' => $extractor === 'openai' ? config('bacadulu.ai.model') : null,
                    'prompt_version' => config('bacadulu.ai.prompt_version'),
                    'started_at' => now(),
                ]),
                'recovered_job_ids' => $staleJobs->pluck('id')->all(),
            ];
        });
        /** @var AiExtractionJob $job */
        $job = $transaction['job'];
        if ($transaction['recovered_job_ids'] !== []) {
            AuditService::record('ai_extraction.stale_recovered', $document, [
                'job_ids' => $transaction['recovered_job_ids'],
            ]);
        }

        try {
            $result = $extractor === 'local_csv'
                ? $this->csvExtractor->extract($document)
                : $this->openAiExtractor->extract($document);

            $maxRows = (int) config('bacadulu.ai.max_rows', 1000);
            if (count($result['rows']) > $maxRows) {
                throw new ExtractionException("Hasil melebihi batas {$maxRows} baris per ekstraksi.", 'row_limit');
            }
            if ($result['rows'] === []) {
                throw new ExtractionException('Tidak ada kandidat observasi yang ditemukan pada dokumen.', 'empty_extraction');
            }

            $variables = $document->dataset->variables->keyBy(fn ($variable) => Str::lower($variable->code));
            $normalized = [];
            $signatures = [];
            foreach (array_values($result['rows']) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $clean = $this->rowValidator->normalize($row, $document->dataset, $variables);
                $signature = Str::lower($clean['variable_code'].'|'.$clean['geography_code'].'|'.$clean['period']);
                if (isset($signatures[$signature])) {
                    $clean['validation_status'] = 'invalid';
                    $clean['validation_issues'][] = 'Duplikat dengan kandidat baris '.($signatures[$signature] + 1).'. Edit atau tolak salah satunya.';
                } else {
                    $signatures[$signature] = $index;
                }

                $normalized[] = $clean + [
                    'ai_extraction_job_id' => $job->id,
                    'source_document_id' => $document->id,
                    'dataset_id' => $document->dataset_id,
                    'row_index' => $index + 1,
                    'status' => 'proposed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($normalized === []) {
                throw new ExtractionException('Tidak ada kandidat observasi yang valid untuk ditampilkan.', 'empty_extraction');
            }

            DB::transaction(function () use ($job, $document, $result, $normalized): void {
                $databaseRows = array_map(function (array $row): array {
                    $row['validation_issues'] = json_encode($row['validation_issues'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

                    return $row;
                }, $normalized);
                AiExtractionRow::insert($databaseRows);
                $average = collect($normalized)->avg('confidence');
                $job->update([
                    'status' => 'review',
                    'overall_confidence' => $average === null ? null : round((float) $average, 4),
                    'response_id' => $result['response_id'],
                    'usage' => $result['usage'],
                    'summary' => $result['summary'],
                    'completed_at' => now(),
                    'error_code' => null,
                    'error_message' => null,
                ]);
                $document->update(['status' => 'ready']);
            });

            AuditService::record('ai_extraction.completed', $job, [
                'dataset_id' => $document->dataset_id,
                'source_document_id' => $document->id,
                'extractor' => $extractor,
                'rows' => count($normalized),
            ]);

            return $job->fresh();
        } catch (Throwable $exception) {
            $safe = $exception instanceof ExtractionException
                ? $exception
                : new ExtractionException('Ekstraksi gagal karena gangguan internal. Periksa log server dan coba lagi.', 'internal_error');

            $job->update([
                'status' => 'failed',
                'error_code' => $safe->safeCode,
                'error_message' => $safe->getMessage(),
                'completed_at' => now(),
            ]);
            $document->update(['status' => 'failed']);
            Log::error('Dataset extraction failed.', [
                'job_id' => $job->id,
                'source_document_id' => $document->id,
                'error_class' => $exception::class,
                'safe_code' => $safe->safeCode,
            ]);
            AuditService::record('ai_extraction.failed', $job, ['error_code' => $safe->safeCode]);

            throw $safe;
        }
    }
}
