<?php

namespace App\Services;

use App\Models\DataAccessRequest;
use App\Models\Dataset;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatasetExportService
{
    public function forApprovedRequest(DataAccessRequest $accessRequest): StreamedResponse
    {
        $query = $this->baseQuery($accessRequest->dataset)
            ->whereIn('dataset_variable_id', $accessRequest->variable_ids);

        if ($accessRequest->geographies) {
            $query->whereIn('geography_code', $accessRequest->geographies);
        }

        if ($accessRequest->periods) {
            $query->whereIn('period', $accessRequest->periods);
        }

        return $this->stream(
            $accessRequest->dataset,
            $query,
            strtolower($accessRequest->request_number).'.csv'
        );
    }

    public function forOpenDataset(Dataset $dataset, array $filters): StreamedResponse
    {
        $query = $this->baseQuery($dataset);

        if (! empty($filters['variables'])) {
            $query->whereIn('dataset_variable_id', $filters['variables']);
        }

        if (! empty($filters['geographies'])) {
            $query->whereIn('geography_code', $filters['geographies']);
        }

        if (! empty($filters['periods'])) {
            $query->whereIn('period', $filters['periods']);
        }

        return $this->stream($dataset, $query, $dataset->slug.'-export.csv');
    }

    private function baseQuery(Dataset $dataset): Builder
    {
        return $dataset->observations()
            ->whereIn('quality_status', ['reviewed', 'verified'])
            ->whereHas('variable', fn ($query) => $query->where('is_active', true))
            ->with('variable:id,code,name,unit')
            ->getQuery();
    }

    private function stream(Dataset $dataset, Builder $query, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($dataset, $query): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'dataset_code',
                'variable_code',
                'variable_name',
                'unit',
                'geography_code',
                'geography_name',
                'period',
                'value',
                'quality_status',
                'source_reference',
            ], ',', '"', '');

            $query->chunkById(1000, function ($observations) use ($dataset, $handle): void {
                foreach ($observations as $observation) {
                    fputcsv($handle, [
                        $this->safeCell($dataset->code),
                        $this->safeCell($observation->variable->code),
                        $this->safeCell($observation->variable->name),
                        $this->safeCell($observation->variable->unit),
                        $this->safeCell($observation->geography_code),
                        $this->safeCell($observation->geography_name),
                        $this->safeCell($observation->period),
                        $observation->value_numeric ?? $this->safeCell($observation->value_text),
                        $this->safeCell($observation->quality_status),
                        $this->safeCell($observation->source_reference),
                    ], ',', '"', '');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function safeCell(?string $value): ?string
{
    /*
     * Excel dan spreadsheet lain dapat mengabaikan spasi atau
     * karakter kontrol sebelum menjalankan formula.
     *
     * Contoh berbahaya:
     * =SUM(A1:A2)
     *   =2+2
     * +CMD
     * @IMPORTXML(...)
     */
    if (
        $value !== null
        && preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1
    ) {
        return "'".$value;
    }

    return $value;
}
}
