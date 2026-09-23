<?php

namespace App\Services\Ai;

use App\Models\AiExtractionJob;
use App\Models\DatasetObservation;
use App\Models\DatasetVariable;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatasetExtractionApprovalService
{
    public function approve(AiExtractionJob $job, User $reviewer): array
    {
        return DB::transaction(function () use ($job, $reviewer): array {
            /** @var AiExtractionJob $locked */
            $locked = AiExtractionJob::query()->lockForUpdate()->findOrFail($job->id);
            if (! $locked->isReviewable()) {
                throw new ExtractionException('Ekstraksi ini sudah ditutup atau belum siap direview.', 'job_not_reviewable');
            }

            if ($locked->rows()->where('status', 'proposed')->exists()) {
                throw new ExtractionException('Semua kandidat harus diberi keputusan Terima atau Tolak sebelum finalisasi.', 'unreviewed_rows');
            }

            $accepted = $locked->rows()->where('status', 'accepted')->orderBy('row_index')->lockForUpdate()->get();
            if ($accepted->isEmpty()) {
                throw new ExtractionException('Tidak ada kandidat yang diterima untuk dimasukkan ke dataset.', 'no_accepted_rows');
            }
            if ($accepted->contains(fn ($row) => $row->validation_status === 'invalid')) {
                throw new ExtractionException('Masih ada kandidat tidak valid yang diterima. Edit atau tolak baris tersebut.', 'invalid_accepted_row');
            }

            $signatures = [];
            foreach ($accepted as $row) {
                $signature = Str::lower($row->variable_code.'|'.$row->geography_code.'|'.$row->period);
                if (isset($signatures[$signature])) {
                    throw new ExtractionException('Terdapat kandidat duplikat yang sama-sama diterima. Sisakan satu baris saja.', 'duplicate_accepted_rows');
                }
                $signatures[$signature] = true;
            }

            $dataset = $locked->dataset()->lockForUpdate()->firstOrFail();
            $createdVariables = 0;
            $createdObservations = 0;
            $updatedObservations = 0;

            foreach ($accepted as $row) {
                $variable = DatasetVariable::firstOrCreate(
                    ['dataset_id' => $dataset->id, 'code' => $row->variable_code],
                    [
                        'name' => $row->variable_name,
                        'definition' => $row->variable_definition,
                        'unit' => $row->unit,
                        'data_type' => $row->data_type,
                        'category' => 'AI-assisted import',
                        'access_tier' => $dataset->access_type === 'open' ? 'open' : 'standard',
                        'price_per_cell' => 0,
                        'is_active' => true,
                    ]
                );
                if ($variable->wasRecentlyCreated) {
                    $createdVariables++;
                }

                $observation = DatasetObservation::updateOrCreate(
                    [
                        'dataset_id' => $dataset->id,
                        'dataset_variable_id' => $variable->id,
                        'geography_code' => $row->geography_code,
                        'period' => $row->period,
                    ],
                    [
                        'geography_name' => $row->geography_name,
                        'value_numeric' => $row->value_numeric,
                        'value_text' => $row->value_text,
                        'source_reference' => Str::limit($locked->sourceDocument->original_name.' — '.$row->source_locator, 255, ''),
                        'quality_status' => 'unreviewed',
                    ]
                );

                $observation->wasRecentlyCreated ? $createdObservations++ : $updatedObservations++;
                $row->update([
                    'matched_variable_id' => $variable->id,
                    'applied_observation_id' => $observation->id,
                    'status' => 'applied',
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                ]);
            }

            $wasPublished = $dataset->status === 'published';
            $dataset->update([
                'status' => $wasPublished ? 'draft' : $dataset->status,
                'published_at' => $wasPublished ? null : $dataset->published_at,
                'last_updated_at' => now(),
            ]);
            $locked->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $result = [
                'variables_created' => $createdVariables,
                'observations_created' => $createdObservations,
                'observations_updated' => $updatedObservations,
                'publication_reset' => $wasPublished,
            ];
            AuditService::record('ai_extraction.approved', $locked, $result + [
                'dataset_id' => $dataset->id,
                'source_document_id' => $locked->source_document_id,
            ]);

            return $result;
        });
    }

    public function reject(AiExtractionJob $job, User $reviewer, ?string $reason): void
    {
        DB::transaction(function () use ($job, $reviewer, $reason): void {
            /** @var AiExtractionJob $locked */
            $locked = AiExtractionJob::query()->lockForUpdate()->findOrFail($job->id);
            if (! $locked->isReviewable()) {
                throw new ExtractionException('Ekstraksi ini sudah ditutup atau belum siap direview.', 'job_not_reviewable');
            }

            $locked->rows()->whereIn('status', ['proposed', 'accepted'])->update([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
            $locked->update([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'summary' => $reason ? Str::limit($locked->summary."\nAlasan penolakan: ".$reason, 10000, '') : $locked->summary,
            ]);
            AuditService::record('ai_extraction.rejected', $locked, [
                'dataset_id' => $locked->dataset_id,
                'reason' => $reason ? Str::limit($reason, 500, '') : null,
            ]);
        });
    }
}
