<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Models\DatasetObservation;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DatasetObservationController extends Controller
{
    public function index(
        Request $request,
        Dataset $dataset
    ): View {
        $activeObservations = $dataset->observations()
            ->whereHas(
                'variable',
                fn ($query) => $query->where('is_active', true)
            );

        $observations = $dataset->observations()
            ->with('variable')
            ->when(
                $request->filled('variable'),
                fn ($query) => $query->where(
                    'dataset_variable_id',
                    $request->integer('variable')
                )
            )
            ->when(
                $request->filled('quality'),
                fn ($query) => $query->where(
                    'quality_status',
                    $request->string('quality')
                )
            )
            ->when(
                $request->filled('q'),
                function ($query) use ($request): void {
                    $term = '%'.$request
                        ->string('q')
                        ->trim().'%';

                    $query->where(
                        fn ($inner) => $inner
                            ->where('geography_name', 'like', $term)
                            ->orWhere('geography_code', 'like', $term)
                            ->orWhere('period', 'like', $term)
                    );
                }
            )
            ->orderByDesc('period')
            ->orderBy('geography_name')
            ->paginate(40)
            ->withQueryString();

        $qualityCounts = $dataset->observations()
            ->selectRaw('quality_status, COUNT(*) as total')
            ->groupBy('quality_status')
            ->pluck('total', 'quality_status');

        $totalObservationCount = $dataset
            ->observations()
            ->count();

        $activeObservationCount = (
            clone $activeObservations
        )->count();

        $inactiveObservationCount = $dataset
            ->observations()
            ->whereHas(
                'variable',
                fn ($query) => $query->where('is_active', false)
            )
            ->count();

        $pendingQualityCount = (
            clone $activeObservations
        )
            ->whereNotIn(
                'quality_status',
                ['reviewed', 'verified']
            )
            ->count();

        return view('admin.observations.index', [
            'dataset' => $dataset,
            'observations' => $observations,
            'variables' => $dataset
                ->variables()
                ->orderBy('name')
                ->get(),
            'qualityCounts' => $qualityCounts,
            'totalObservationCount' => $totalObservationCount,
            'activeObservationCount' => $activeObservationCount,
            'inactiveObservationCount' => $inactiveObservationCount,
            'pendingQualityCount' => $pendingQualityCount,
        ]);
    }

    public function update(
        Request $request,
        Dataset $dataset,
        DatasetObservation $observation
    ): RedirectResponse {
        $this->assertBelongsToDataset(
            $dataset,
            $observation
        );

        $validated = $request->validate([
            'quality_status' => [
                'required',
                Rule::in([
                    'unreviewed',
                    'reviewed',
                    'verified',
                    'flagged',
                ]),
            ],
        ]);

        $observation->update($validated);

        $wasPublished = $this->resetPublication($dataset);

        $dataset->update([
            'last_updated_at' => now(),
        ]);

        AuditService::record(
            'observation.quality_changed',
            $observation,
            [
                'quality_status' => $observation->quality_status,
                'dataset_id' => $dataset->id,
            ]
        );

        $pendingQualityCount = $dataset
            ->observations()
            ->whereHas(
                'variable',
                fn ($query) => $query->where('is_active', true)
            )
            ->whereNotIn(
                'quality_status',
                ['reviewed', 'verified']
            )
            ->count();

        $message = 'Status kualitas observasi diperbarui.';

        if ($wasPublished) {
            $message .= ' Dataset dikembalikan ke draft.';
        }

        if ($pendingQualityCount > 0) {
            $message .= ' Tersisa '
                .number_format($pendingQualityCount)
                .' angka yang perlu ditangani.';
        }

        $response = back()->with('success', $message);

        $hasActiveObservations = $dataset
            ->observations()
            ->whereHas(
                'variable',
                fn ($query) => $query->where('is_active', true)
            )
            ->exists();

        if (
            $pendingQualityCount === 0
            && $hasActiveObservations
        ) {
            $response->with('admin_next_step', [
                'title' => 'Pemeriksaan data selesai',
                'description' => 'Semua angka untuk variabel aktif sudah ditinjau. Periksa kesiapan koleksi, lalu terbitkan ke katalog.',
                'label' => 'Lanjut ke publikasi',
                'url' => route(
                    'admin.datasets.edit',
                    $dataset
                ).'#publication',
                'nav' => 'datasets',
            ]);
        }

        return $response;
    }

    public function destroy(
        Dataset $dataset,
        DatasetObservation $observation
    ): RedirectResponse {
        $this->assertBelongsToDataset(
            $dataset,
            $observation
        );

        AuditService::record(
            'observation.deleted',
            $observation,
            [
                'dataset_id' => $dataset->id,
                'period' => $observation->period,
                'geography_code' => $observation->geography_code,
            ]
        );

        $observation->delete();

        $wasPublished = $this->resetPublication($dataset);

        $dataset->update([
            'last_updated_at' => now(),
        ]);

        $message = 'Observasi berhasil dihapus.';

        if ($wasPublished) {
            $message .= ' Dataset dikembalikan ke draft.';
        }

        return back()->with('success', $message);
    }

    private function assertBelongsToDataset(
        Dataset $dataset,
        DatasetObservation $observation
    ): void {
        abort_unless(
            $observation->dataset_id === $dataset->id,
            404
        );
    }

    private function resetPublication(
        Dataset $dataset
    ): bool {
        if ($dataset->status !== 'published') {
            return false;
        }

        $dataset->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        return true;
    }
}