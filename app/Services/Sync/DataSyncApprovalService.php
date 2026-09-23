<?php

namespace App\Services\Sync;

use App\Models\DataSyncRun;
use App\Models\DatasetObservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DataSyncApprovalService
{
    public function apply(DataSyncRun $run, User $reviewer): array
    {
        return DB::transaction(function () use ($run, $reviewer): array {
            $lockedRun = DataSyncRun::query()->lockForUpdate()->findOrFail($run->id);
            if (! $lockedRun->isReviewable()) {
                throw new DataSyncException('Hanya sinkronisasi berstatus review yang dapat diterapkan.', 'sync_not_reviewable');
            }
            if ($lockedRun->rows()->where('status', 'proposed')->exists()) {
                throw new DataSyncException('Putuskan seluruh baris terlebih dahulu sebelum finalisasi.', 'sync_rows_pending');
            }
            if (! $lockedRun->rows()->where('status', 'accepted')->exists()) {
                throw new DataSyncException('Tidak ada baris yang diterima untuk diterapkan.', 'sync_nothing_accepted');
            }
            if ($lockedRun->rows()->where('status', 'accepted')->where('validation_status', 'invalid')->exists()) {
                throw new DataSyncException('Baris invalid tidak dapat diterapkan. Tolak baris tersebut atau jalankan ulang konektor.', 'sync_invalid_rows_accepted');
            }

            $lockedRun->load('connector.dataset');
            $dataset = $lockedRun->connector->dataset;
            $created = 0;
            $updated = 0;
            $unchanged = 0;

            $lockedRun->rows()->where('status', 'accepted')->orderBy('id')->chunkById(500, function ($rows) use ($dataset, &$created, &$updated, &$unchanged): void {
                foreach ($rows as $row) {
                    $observation = DatasetObservation::query()
                        ->where('dataset_id', $dataset->id)
                        ->where('dataset_variable_id', $row->dataset_variable_id)
                        ->where('geography_code', $row->geography_code)
                        ->where('period', $row->period)
                        ->lockForUpdate()
                        ->first();
                    $values = [
                        'geography_name' => $row->geography_name,
                        'value_numeric' => $row->value_numeric,
                        'value_text' => $row->value_text,
                        'source_reference' => $row->source_reference,
                    ];

                    if (! $observation) {
                        DatasetObservation::create($values + [
                            'dataset_id' => $dataset->id,
                            'dataset_variable_id' => $row->dataset_variable_id,
                            'geography_code' => $row->geography_code,
                            'period' => $row->period,
                            'quality_status' => 'unreviewed',
                        ]);
                        $created++;
                    } elseif ($observation->value_numeric === $row->value_numeric
                        && (string) $observation->value_text === (string) $row->value_text
                        && $observation->geography_name === $row->geography_name) {
                        $unchanged++;
                    } else {
                        $observation->update($values + ['quality_status' => 'unreviewed']);
                        $updated++;
                    }
                }
            });

            $hasChanges = $created + $updated > 0;
            $publicationReset = $hasChanges && $dataset->status === 'published';
            if ($hasChanges) {
                $dataset->update([
                    'status' => $publicationReset ? 'draft' : $dataset->status,
                    'published_at' => $publicationReset ? null : $dataset->published_at,
                    'last_updated_at' => now(),
                ]);
            }
            $lockedRun->rows()->where('status', 'accepted')->update([
                'status' => 'applied',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
            $lockedRun->update([
                'status' => 'applied',
                'created_rows_count' => $created,
                'updated_rows_count' => $updated,
                'unchanged_rows_count' => $unchanged,
                'applied_at' => now(),
            ]);

            return compact('created', 'updated', 'unchanged', 'publicationReset');
        });
    }
}
