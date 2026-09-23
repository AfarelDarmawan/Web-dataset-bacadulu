<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DataAccessRequest;
use App\Models\Dataset;
use App\Services\AuditService;
use App\Services\DatasetExportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatasetDownloadController extends Controller
{
    public function open(Request $request, Dataset $dataset, DatasetExportService $exporter): StreamedResponse
    {
        $dataset->loadMissing('provider');
        abort_unless($dataset->isPubliclyAvailable() && $dataset->isOpen(), 404);

        $validated = $request->validate([
            'variables' => ['nullable', 'array', 'max:200'],
            'variables.*' => ['integer'],
            'geographies' => ['nullable', 'array', 'max:500'],
            'geographies.*' => ['string', 'max:100'],
            'periods' => ['nullable', 'array', 'max:500'],
            'periods.*' => ['string', 'max:30'],
        ]);

        if (! empty($validated['variables'])) {
            $validCount = $dataset->variables()->where('is_active', true)->whereIn('id', $validated['variables'])->count();
            if ($validCount !== count(array_unique($validated['variables']))) {
                throw ValidationException::withMessages(['variables' => 'Pilihan variabel tidak valid.']);
            }
        }

        foreach (['geographies' => 'geography_code', 'periods' => 'period'] as $input => $column) {
            if (! empty($validated[$input])) {
                $selected = array_values(array_unique($validated[$input]));
                $scope = $dataset->observations()
                    ->whereIn('quality_status', ['reviewed', 'verified'])
                    ->whereHas('variable', fn ($query) => $query->where('is_active', true))
                    ->whereIn($column, $selected)
                    ->when(! empty($validated['variables']), fn ($query) => $query->whereIn('dataset_variable_id', $validated['variables']));

                $validCount = $scope->distinct()
                    ->count($column);

                if ($validCount !== count($selected)) {
                    throw ValidationException::withMessages([$input => 'Pilihan cakupan data tidak valid.']);
                }

                $validated[$input] = $selected;
            }
        }

        if (! empty($validated['variables'])) {
            $validated['variables'] = array_values(array_unique($validated['variables']));
        }

        AuditService::record('dataset.open_exported', $dataset, [
            'filters' => array_keys(array_filter($validated)),
        ]);

        return $exporter->forOpenDataset($dataset, $validated);
    }

    public function approved(
        Request $request,
        DataAccessRequest $accessRequest,
        DatasetExportService $exporter
    ): StreamedResponse {
        abort_unless($accessRequest->user_id === $request->user()->id, 404);
        $accessRequest->load('dataset');
        $accessRequest->dataset?->loadMissing('provider');
        abort_unless($accessRequest->dataset?->isPubliclyAvailable(), 403, 'Dataset tidak sedang tersedia untuk diunduh.');
        abort_unless($accessRequest->canDownload(), 403, 'Akses unduhan belum tersedia atau sudah kedaluwarsa.');

        $accessRequest->forceFill(['downloaded_at' => now()])->save();
        AuditService::record('access_request.downloaded', $accessRequest);

        return $exporter->forApprovedRequest($accessRequest);
    }
}
