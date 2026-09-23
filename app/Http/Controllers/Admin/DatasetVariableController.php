<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Models\DatasetVariable;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DatasetVariableController extends Controller
{
    public function index(Dataset $dataset): View
    {
        return view('admin.variables.index', [
            'dataset' => $dataset,
            'variables' => $dataset->variables()->withCount(['observations', 'dataConnectors'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create(Dataset $dataset): View
    {
        return view('admin.variables.create', compact('dataset'));
    }

    public function store(Request $request, Dataset $dataset): RedirectResponse
    {
        $validated = $this->validateVariable($request, $dataset);
        $validated['dataset_id'] = $dataset->id;
        $validated['is_active'] = $request->boolean('is_active');

        $variable = DatasetVariable::create($validated);
        $wasPublished = $this->resetPublication($dataset);
        AuditService::record('variable.created', $variable, ['dataset_id' => $dataset->id]);

        return redirect()->route('admin.datasets.variables.index', $dataset)
            ->with('success', 'Variabel berhasil ditambahkan.'.($wasPublished ? ' Dataset dikembalikan ke draft.' : ''))
            ->with('admin_next_step', [
                'title' => 'Variabel sudah ditambahkan',
                'description' => 'Isi nilainya lewat CSV, atau kembali ke daftar untuk menambah variabel lain.',
                'label' => 'Impor observasi',
                'url' => route('admin.datasets.import.create', $dataset),
                'nav' => 'datasets',
            ]);
    }

    public function edit(Dataset $dataset, DatasetVariable $variable): View
    {
        $this->assertBelongsToDataset($dataset, $variable);

        return view('admin.variables.edit', compact('dataset', 'variable'));
    }

    public function update(Request $request, Dataset $dataset, DatasetVariable $variable): RedirectResponse
    {
        $this->assertBelongsToDataset($dataset, $variable);
        $validated = $this->validateVariable($request, $dataset, $variable);
        $validated['is_active'] = $request->boolean('is_active');
        $variable->update($validated);
        $wasPublished = $this->resetPublication($dataset);
        AuditService::record('variable.updated', $variable);

        return redirect()->route('admin.datasets.variables.index', $dataset)
            ->with('success', 'Variabel berhasil diperbarui.'.($wasPublished ? ' Dataset dikembalikan ke draft.' : ''));
    }

    public function destroy(Dataset $dataset, DatasetVariable $variable): RedirectResponse
    {
        $this->assertBelongsToDataset($dataset, $variable);

        if ($variable->observations()->exists()) {
            return back()->with('error', 'Variabel tidak dapat dihapus karena sudah memiliki observasi.');
        }
        if ($variable->dataConnectors()->exists()) {
            return back()->with('error', 'Variabel tidak dapat dihapus karena terhubung ke otomatisasi data. Jeda konektornya dan pertahankan variabel untuk menjaga audit trail.');
        }

        AuditService::record('variable.deleted', $variable, ['code' => $variable->code]);
        $variable->delete();
        $wasPublished = $this->resetPublication($dataset);

        return back()->with('success', 'Variabel berhasil dihapus.'.($wasPublished ? ' Dataset dikembalikan ke draft.' : ''));
    }

    private function validateVariable(Request $request, Dataset $dataset, ?DatasetVariable $variable = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('dataset_variables', 'code')
                    ->where(fn ($query) => $query->where('dataset_id', $dataset->id))
                    ->ignore($variable?->id),
            ],
            'name' => ['required', 'string', 'max:220'],
            'definition' => ['nullable', 'string', 'max:5000'],
            'unit' => ['nullable', 'string', 'max:80'],
            'data_type' => ['required', Rule::in(['numeric', 'text', 'percentage', 'currency', 'index'])],
            'category' => ['nullable', 'string', 'max:100'],
            'access_tier' => ['required', Rule::in(['open', 'standard', 'premium'])],
            'price_per_cell' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function assertBelongsToDataset(Dataset $dataset, DatasetVariable $variable): void
    {
        abort_unless($variable->dataset_id === $dataset->id, 404);
    }

    private function resetPublication(Dataset $dataset): bool
    {
        if ($dataset->status !== 'published') {
            return false;
        }

        $dataset->update(['status' => 'draft', 'published_at' => null]);

        return true;
    }
}
