<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataConnector;
use App\Models\DataSyncRun;
use App\Services\AuditService;
use App\Services\Sync\BpsConnectorService;
use App\Services\Sync\DataSyncApprovalService;
use App\Services\Sync\DataSyncException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DataSyncController extends Controller
{
    public function store(Request $request, DataConnector $connector, BpsConnectorService $service): RedirectResponse
    {
        try {
            $run = $service->run($connector, $request->user());
        } catch (DataSyncException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        AuditService::record('data_sync.fetched', $run, ['rows' => $run->rows_count, 'connector_id' => $connector->id]);

        return redirect()->route('admin.automation.runs.show', [$connector, $run])
            ->with('success', 'Data BPS masuk ke staging. Periksa perubahan sebelum diterapkan.');
    }

    public function show(Request $request, DataConnector $connector, DataSyncRun $run): View
    {
        $this->assertRunBelongsToConnector($connector, $run);
        $run->load(['connector.dataset.provider', 'connector.variable', 'triggerer']);
        $rows = $run->rows()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('validation'), fn ($query) => $query->where('validation_status', $request->string('validation')))
            ->when($request->filled('action'), fn ($query) => $query->where('proposed_action', $request->string('action')))
            ->orderBy('row_index')
            ->paginate(50)
            ->withQueryString();

        return view('admin.automation.run', [
            'connector' => $connector,
            'run' => $run,
            'rows' => $rows,
            'counts' => $run->rows()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function bulk(Request $request, DataConnector $connector, DataSyncRun $run): RedirectResponse
    {
        $this->assertRunBelongsToConnector($connector, $run);
        abort_unless($run->isReviewable(), 409);
        $validated = $request->validate([
            'action' => ['required', Rule::in(['accept_selected', 'reject_selected', 'accept_all_valid', 'reject_all_invalid'])],
            'row_ids' => ['nullable', 'array', 'max:1000'],
            'row_ids.*' => ['integer', 'distinct'],
        ]);
        $accept = str_starts_with($validated['action'], 'accept_');

        $count = DB::transaction(function () use ($run, $validated, $accept, $request): int {
            $lockedRun = DataSyncRun::query()->lockForUpdate()->findOrFail($run->id);
            abort_unless($lockedRun->isReviewable(), 409);
            $query = $lockedRun->rows()->where('status', 'proposed');
            if (str_ends_with($validated['action'], '_selected')) {
                $ids = $validated['row_ids'] ?? [];
                if ($ids === []) {
                    throw ValidationException::withMessages(['row_ids' => 'Pilih minimal satu baris.']);
                }
                $query->whereIn('id', $ids);
                if ((clone $query)->lockForUpdate()->count() !== count($ids)) {
                    abort(404);
                }
            } elseif ($validated['action'] === 'accept_all_valid') {
                $query->whereIn('validation_status', ['valid', 'warning']);
            } else {
                $query->where('validation_status', 'invalid');
            }
            if ($accept && (clone $query)->where('validation_status', 'invalid')->exists()) {
                throw ValidationException::withMessages(['row_ids' => 'Baris invalid tidak dapat diterima. Perbaiki konektor atau tolak baris tersebut.']);
            }

            return $query->update([
                'status' => $accept ? 'accepted' : 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
        });
        AuditService::record('data_sync.rows_reviewed', $run, [
            'decision' => $accept ? 'accepted' : 'rejected',
            'rows' => $count,
        ]);

        return back()->with('success', "{$count} baris berhasil ".($accept ? 'diterima' : 'ditolak').'.');
    }

    public function apply(
        Request $request,
        DataConnector $connector,
        DataSyncRun $run,
        DataSyncApprovalService $service
    ): RedirectResponse {
        $this->assertRunBelongsToConnector($connector, $run);
        try {
            $result = $service->apply($run, $request->user());
        } catch (DataSyncException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        AuditService::record('data_sync.applied', $run, $result);
        $message = "Sinkronisasi diterapkan: {$result['created']} observasi baru, {$result['updated']} diperbarui, {$result['unchanged']} tidak berubah.";
        if ($result['publicationReset']) {
            $message .= ' Dataset dikembalikan ke draft untuk pemeriksaan kualitas.';
        }

        return redirect()->route('admin.datasets.observations.index', $connector->dataset_id)
            ->with('success', $message)
            ->with('admin_next_step', [
                'title' => 'Sinkronisasi sudah diterapkan',
                'description' => 'Periksa observasi yang berubah sebelum menerbitkan ulang koleksi.',
                'label' => 'Periksa kualitas',
                'url' => route('admin.quality.index'),
                'nav' => 'quality',
            ]);
    }

    public function reject(Request $request, DataConnector $connector, DataSyncRun $run): RedirectResponse
    {
        $this->assertRunBelongsToConnector($connector, $run);
        DB::transaction(function () use ($run, $request): void {
            $lockedRun = DataSyncRun::query()->lockForUpdate()->findOrFail($run->id);
            abort_unless($lockedRun->isReviewable(), 409);
            $lockedRun->rows()->where('status', 'proposed')->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
            $lockedRun->update(['status' => 'rejected', 'completed_at' => now()]);
        });
        AuditService::record('data_sync.rejected', $run);

        return redirect()->route('admin.automation.index')->with('success', 'Hasil sinkronisasi ditutup tanpa mengubah observasi.');
    }

    private function assertRunBelongsToConnector(DataConnector $connector, DataSyncRun $run): void
    {
        abort_unless($run->data_connector_id === $connector->id, 404);
    }
}
