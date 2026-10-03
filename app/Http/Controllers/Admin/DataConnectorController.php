<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataConnector;
use App\Models\DataSyncRun;
use App\Models\Dataset;
use App\Models\DatasetVariable;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DataConnectorController extends Controller
{
    public function index(Request $request): View
    {
        $selectedDataset = $request->integer('dataset') > 0
            ? Dataset::find($request->integer('dataset'))
            : null;

        return view('admin.automation.index', [
            'connectors' => DataConnector::query()
                ->when($selectedDataset, fn ($query) => $query->where('dataset_id', $selectedDataset->id))
                ->with(['dataset.provider', 'variable'])
                ->withCount(['runs', 'runs as open_runs_count' => fn ($query) => $query->whereIn('status', ['queued', 'processing', 'review'])])
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'recentRuns' => DataSyncRun::query()
                ->when($selectedDataset, fn ($query) => $query->whereHas('connector', fn ($connectors) => $connectors->where('dataset_id', $selectedDataset->id)))
                ->with(['connector.dataset', 'connector.variable', 'triggerer'])
                ->latest()
                ->limit(8)
                ->get(),
            'stats' => [
                'active' => DataConnector::where('status', 'active')->when($selectedDataset, fn ($query) => $query->where('dataset_id', $selectedDataset->id))->count(),
                'review' => DataSyncRun::where('status', 'review')->when($selectedDataset, fn ($query) => $query->whereHas('connector', fn ($connectors) => $connectors->where('dataset_id', $selectedDataset->id)))->count(),
                'failed' => DataSyncRun::where('status', 'failed')->when($selectedDataset, fn ($query) => $query->whereHas('connector', fn ($connectors) => $connectors->where('dataset_id', $selectedDataset->id)))->count(),
                'applied' => DataSyncRun::where('status', 'applied')->when($selectedDataset, fn ($query) => $query->whereHas('connector', fn ($connectors) => $connectors->where('dataset_id', $selectedDataset->id)))->count(),
            ],
            'selectedDataset' => $selectedDataset,
        ]);
    }

    public function create(Request $request): View
    {
        $selectedDataset = $request->integer('dataset') > 0
            ? Dataset::with([
                'provider',
                'variables' => fn ($query) => $query->orderBy('name'),
            ])->find($request->integer('dataset'))
            : null;

        return view('admin.automation.create', [
            'connector' => new DataConnector(['status' => 'active', 'schedule' => 'manual', 'config' => ['language' => 'ind']]),
            'datasets' => $this->availableDatasets(
                $selectedDataset?->data_scope === 'regional' ? $selectedDataset->id : null
            ),
            'selectedDataset' => $selectedDataset,
            'setupIssues' => $this->setupIssues($selectedDataset),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$validated, $variable] = $this->validated($request);
        $connector = DataConnector::create([
            'dataset_id' => $variable->dataset_id,
            'dataset_variable_id' => $variable->id,
            'created_by' => $request->user()->id,
            'name' => $validated['name'],
            'type' => 'bps',
            'status' => 'active',
            'schedule' => $validated['schedule'],
            'config' => $this->connectorConfig($validated),
        ]);
        $connector->update(['next_sync_at' => $connector->nextScheduledAt()]);
        AuditService::record('data_connector.created', $connector, ['type' => 'bps']);

        return redirect()->route('admin.automation.index', ['dataset' => $variable->dataset_id])
            ->with('success', 'Konektor BPS dibuat. Data belum masuk ke katalog sampai sinkronisasi dijalankan, ditinjau, dan diterapkan.')
            ->with('admin_next_step', [
                'title' => 'Konektor siap digunakan',
                'description' => 'Jalankan sinkronisasi pertama. Hasil BPS akan masuk ke staging agar dapat diperiksa sebelum diterapkan ke koleksi.',
                'label' => 'Buka pusat sinkronisasi',
                'url' => route('admin.automation.index', ['dataset' => $variable->dataset_id]),
                'nav' => 'automation',
            ]);
    }

    public function edit(DataConnector $connector): View
    {
        return view('admin.automation.edit', [
            'connector' => $connector->load(['dataset', 'variable']),
            'datasets' => $this->availableDatasets(null, $connector->dataset_variable_id),
            'selectedDataset' => $connector->dataset->loadMissing([
                'provider',
                'variables' => fn ($query) => $query->orderBy('name'),
            ]),
            'setupIssues' => [],
        ]);
    }

    public function update(Request $request, DataConnector $connector): RedirectResponse
    {
        [$validated, $variable] = $this->validated($request, $connector);
        if ($connector->dataset_variable_id !== $variable->id && $connector->runs()->exists()) {
            throw ValidationException::withMessages([
                'dataset_variable_id' => 'Tujuan konektor yang sudah memiliki riwayat tidak dapat dipindahkan. Buat konektor baru agar audit trail tetap benar.',
            ]);
        }
        $connector->update([
            'dataset_id' => $variable->dataset_id,
            'dataset_variable_id' => $variable->id,
            'name' => $validated['name'],
            'schedule' => $validated['schedule'],
            'config' => $this->connectorConfig($validated),
            'next_sync_at' => $connector->nextScheduledAt(schedule: $validated['schedule']),
            'last_error' => null,
            'consecutive_failures' => 0,
        ]);
        AuditService::record('data_connector.updated', $connector);

        return redirect()->route('admin.automation.index', ['dataset' => $variable->dataset_id])->with('success', 'Konfigurasi konektor diperbarui.');
    }

    public function toggle(DataConnector $connector): RedirectResponse
    {
        $status = $connector->isActive() ? 'paused' : 'active';
        $connector->update([
            'status' => $status,
            'next_sync_at' => $status === 'active' ? $connector->nextScheduledAt() : null,
        ]);
        AuditService::record('data_connector.status_changed', $connector, ['status' => $status]);

        return back()->with('success', $status === 'active' ? 'Konektor diaktifkan.' : 'Konektor dijeda.');
    }

    public function destroy(DataConnector $connector): RedirectResponse
    {
        if ($connector->runs()->exists()) {
            return back()->with('error', 'Konektor yang memiliki riwayat tidak boleh dihapus. Jeda konektor untuk mempertahankan audit trail.');
        }
        AuditService::record('data_connector.deleted', $connector, ['name' => $connector->name]);
        $connector->delete();

        return back()->with('success', 'Konektor kosong berhasil dihapus.');
    }

    private function validated(Request $request, ?DataConnector $connector = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'dataset_variable_id' => [
                'required',
                'integer',
                'exists:dataset_variables,id',
                Rule::unique('data_connectors', 'dataset_variable_id')
                    ->where(fn ($query) => $query->where('type', 'bps'))
                    ->ignore($connector?->id),
            ],
            'domain' => ['required', 'regex:/^\d{4}$/'],
            'variable_id' => ['required', 'integer', 'min:1'],
            'period_ids' => ['required', 'string', 'max:120', 'regex:/^\d+(?:[;:]\d+)*$/'],
            'derived_variable_id' => ['nullable', 'integer', 'min:0'],
            'vertical_variable_id' => ['nullable', 'integer', 'min:0'],
            'derived_period_id' => ['nullable', 'integer', 'min:0'],
            'language' => ['required', Rule::in(['ind', 'eng'])],
            'schedule' => ['required', Rule::in(['manual', 'daily', 'weekly', 'monthly'])],
        ], [
            'domain.regex' => 'Domain BPS harus terdiri dari empat digit, misalnya 0000 atau 3100.',
            'period_ids.regex' => 'Gunakan ID periode BPS, misalnya 115, 115;116, atau 115:120.',
            'dataset_variable_id.unique' => 'Variabel tersebut sudah mempunyai konektor BPS.',
        ]);
        $variable = DatasetVariable::with('dataset')->findOrFail($validated['dataset_variable_id']);
        if ($variable->dataset->data_scope !== 'regional') {
            throw ValidationException::withMessages([
                'dataset_variable_id' => 'Konektor BPS hanya dapat dipasang pada dataset Statistik wilayah & pemerintah.',
            ]);
        }
        if (! $variable->is_active && $connector?->dataset_variable_id !== $variable->id) {
            throw ValidationException::withMessages([
                'dataset_variable_id' => 'Aktifkan variabel tujuan sebelum membuat konektor BPS.',
            ]);
        }

        return [$validated, $variable];
    }

    private function connectorConfig(array $validated): array
    {
        return collect($validated)->only([
            'domain',
            'variable_id',
            'period_ids',
            'derived_variable_id',
            'vertical_variable_id',
            'derived_period_id',
            'language',
        ])->map(fn ($value) => $value === '' ? null : $value)->all();
    }

    private function availableDatasets(?int $onlyDatasetId = null, ?int $includeVariableId = null)
    {
        return Dataset::query()
            ->where('data_scope', 'regional')
            ->when($onlyDatasetId, fn ($query) => $query->whereKey($onlyDatasetId))
            ->with([
                'provider',
                'variables' => function ($query) use ($includeVariableId): void {
                    $query->where(function ($inner) use ($includeVariableId): void {
                        $inner->where('is_active', true);

                        if ($includeVariableId !== null) {
                            $inner->orWhere('id', $includeVariableId);
                        }
                    })->orderBy('name');
                },
            ])
            ->where(function ($query) use ($includeVariableId): void {
                $query->whereHas('variables', fn ($variables) => $variables->where('is_active', true));

                if ($includeVariableId !== null) {
                    $query->orWhereHas(
                        'variables',
                        fn ($variables) => $variables->where('id', $includeVariableId)
                    );
                }
            })
            ->orderBy('title')
            ->get();
    }

    private function setupIssues(?Dataset $dataset): array
    {
        if ($dataset === null) {
            return [];
        }

        $issues = [];

        if ($dataset->data_scope !== 'regional') {
            $issues[] = 'Jenis koleksi masih Perusahaan & ESG. Konektor BPS hanya tersedia untuk Statistik wilayah & pemerintah.';
        }

        if (! $dataset->relationLoaded('provider') || $dataset->provider === null) {
            $issues[] = 'Koleksi belum mempunyai penyedia data.';
        } elseif ($dataset->provider->status !== 'active') {
            $issues[] = 'Penyedia data '.$dataset->provider->name.' belum aktif.';
        }

        if ($dataset->variables->isEmpty()) {
            $issues[] = 'Koleksi belum mempunyai variabel.';
        } elseif ($dataset->variables->where('is_active', true)->isEmpty()) {
            $issues[] = 'Semua variabel pada koleksi ini nonaktif. Aktifkan minimal satu variabel.';
        }

        return $issues;
    }
}
