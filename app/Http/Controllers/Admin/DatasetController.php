<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataProvider;
use App\Models\Dataset;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DatasetController extends Controller
{
    public function index(Request $request): View
    {
        $datasets = Dataset::query()
            ->with('provider')
            ->withCount([
                'variables', 'observations', 'accessRequests',
                'variables as active_variables_count' => fn ($query) => $query->where('is_active', true),
                'observations as active_observations_count' => fn ($query) => $query->whereHas('variable', fn ($variables) => $variables->where('is_active', true)),
                'observations as pending_quality_count' => fn ($query) => $query
                    ->whereHas('variable', fn ($variables) => $variables->where('is_active', true))
                    ->whereNotIn('quality_status', ['reviewed', 'verified']),
            ])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn ($inner) => $inner->where('title', 'like', $term)->orWhere('code', 'like', $term));
            })
            ->when(in_array((string) $request->string('scope'), ['regional', 'corporate'], true), fn ($query) => $query
                ->where('data_scope', $request->string('scope')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.datasets.index', compact('datasets'));
    }

    public function create(): View
    {
        return view('admin.datasets.create', [
            'providers' => DataProvider::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateDataset($request);
        $validated['slug'] = $this->uniqueSlug($validated['title']);
        $validated['code'] = Str::upper($validated['code']);
        $validated['owner_id'] = $request->user()->id;
        $validated['status'] = 'draft';

        $dataset = Dataset::create($validated);
        AuditService::record('dataset.created', $dataset);

        return redirect()->route('admin.datasets.edit', $dataset)
            ->with('success', 'Dataset dibuat. Tambahkan variabel dan observasi sebelum dipublikasikan.')
            ->with('admin_next_step', [
                'title' => 'Koleksi sudah dibuat',
                'description' => 'Sekarang tentukan variabel yang akan dicari peneliti di katalog.',
                'label' => 'Tambah variabel',
                'url' => route('admin.datasets.variables.create', $dataset),
                'nav' => 'datasets',
            ]);
    }

    public function edit(Dataset $dataset): View
    {
        $dataset->load('provider')->loadCount([
            'variables',
            'observations',
            'accessRequests',
            'sourceDocuments',
            'extractionJobs',
            'dataConnectors',
            'variables as active_variables_count' => fn ($query) => $query->where('is_active', true),
            'observations as active_observations_count' => fn ($query) => $query->whereHas('variable', fn ($variables) => $variables->where('is_active', true)),
            'observations as pending_quality_count' => fn ($query) => $query
                ->whereHas('variable', fn ($variables) => $variables->where('is_active', true))
                ->whereNotIn('quality_status', ['reviewed', 'verified']),
        ]);

        return view('admin.datasets.edit', [
            'dataset' => $dataset,
            'providers' => DataProvider::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Dataset $dataset): RedirectResponse
    {
        $wasPublished = $dataset->status === 'published';
        $validated = $this->validateDataset($request, $dataset);
        if ($dataset->data_scope === 'regional'
            && $validated['data_scope'] !== 'regional'
            && $dataset->dataConnectors()->exists()) {
            throw ValidationException::withMessages([
                'data_scope' => 'Cakupan tidak dapat diubah ke Perusahaan & ESG selama dataset memiliki konektor BPS.',
            ]);
        }
        $validated['code'] = Str::upper($validated['code']);
        if ($dataset->title !== $validated['title']) {
            $validated['slug'] = $this->uniqueSlug($validated['title'], $dataset->id);
        }

        if ($wasPublished) {
            $validated['status'] = 'draft';
            $validated['published_at'] = null;
        }

        $dataset->update($validated);
        AuditService::record('dataset.updated', $dataset);

        $message = 'Metadata dataset berhasil diperbarui.';
        if ($wasPublished) {
            $message .= ' Dataset dikembalikan ke draft dan perlu dipublikasikan ulang.';
        }

        return back()->with('success', $message);
    }

    public function publish(Dataset $dataset): RedirectResponse
    {
        if ($dataset->provider()->where('status', 'active')->doesntExist()) {
            return back()->with('error', 'Provider harus aktif sebelum dataset dapat dipublikasikan.');
        }

        if (! $dataset->variables()->where('is_active', true)->exists()) {
            return back()->with('error', 'Tambahkan minimal satu variabel aktif sebelum publikasi.');
        }

        $activeObservations = $dataset->observations()
            ->whereHas('variable', fn ($query) => $query->where('is_active', true));

        if (! (clone $activeObservations)->exists()) {
            return back()->with('error', 'Impor minimal satu observasi untuk variabel aktif sebelum publikasi.');
        }

        if ((clone $activeObservations)->whereNotIn('quality_status', ['reviewed', 'verified'])->exists()) {
            return back()->with('error', 'Semua observasi harus berstatus reviewed atau verified sebelum publikasi.');
        }

        $dataset->update([
            'status' => 'published',
            'published_at' => $dataset->published_at ?? now(),
            'last_updated_at' => now(),
        ]);
        AuditService::record('dataset.published', $dataset);

        return back()->with('success', 'Dataset sekarang tampil pada katalog publik.')
            ->with('admin_next_step', [
                'title' => 'Koleksi sudah terbit',
                'description' => 'Periksa hasilnya di katalog publik untuk memastikan judul dan variabel mudah ditemukan.',
                'label' => 'Lihat katalog',
                'url' => route('datasets.index'),
                'nav' => 'datasets',
            ]);
    }

    public function archive(Dataset $dataset): RedirectResponse
    {
        $dataset->update(['status' => 'archived']);
        AuditService::record('dataset.archived', $dataset);

        return back()->with('success', 'Dataset telah diarsipkan dari katalog publik.');
    }

    public function destroy(Dataset $dataset): RedirectResponse
    {
        if ($dataset->accessRequests()->exists()) {
            return back()->with('error', 'Dataset tidak dapat dihapus karena memiliki riwayat permintaan akses. Arsipkan saja.');
        }

        if ($dataset->sourceDocuments()->exists()) {
            return back()->with('error', 'Dataset memiliki dokumen sumber atau jejak ekstraksi. Hapus dokumen yang belum digunakan, atau arsipkan dataset untuk menjaga audit trail.');
        }

        if ($dataset->dataConnectors()->exists()) {
            return back()->with('error', 'Dataset memiliki konektor otomatis dan riwayat sinkronisasi. Jeda konektor lalu arsipkan dataset untuk menjaga audit trail.');
        }

        AuditService::record('dataset.deleted', $dataset, ['title' => $dataset->title]);
        $dataset->delete();

        return redirect()->route('admin.datasets.index')->with('success', 'Dataset berhasil dihapus.');
    }

    private function validateDataset(Request $request, ?Dataset $dataset = null): array
    {
        return $request->validate([
            'data_provider_id' => ['required', 'exists:data_providers,id'],
            'title' => ['required', 'string', 'max:220'],
            'code' => ['required', 'string', 'max:80', Rule::unique('datasets', 'code')->ignore($dataset?->id)],
            'summary' => ['required', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:20000'],
            'methodology' => ['nullable', 'string', 'max:20000'],
            'data_scope' => ['required', Rule::in(['regional', 'corporate'])],
            'category' => ['required', 'string', 'max:100'],
            'frequency' => ['nullable', 'string', 'max:50'],
            'geographic_level' => ['nullable', 'string', 'max:80'],
            'period_start' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'period_end' => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:period_start'],
            'license' => ['nullable', 'string', 'max:120'],
            'source_url' => ['nullable', 'url:http,https', 'max:255'],
            'access_type' => ['required', Rule::in(['open', 'restricted', 'commercial'])],
        ], [
            'summary.max' => 'Ringkasan katalog maksimal 1.000 karakter. Pindahkan uraian panjang ke kolom Deskripsi lengkap.',
            'description.max' => 'Deskripsi lengkap maksimal 20.000 karakter.',
            'methodology.max' => 'Metodologi maksimal 20.000 karakter.',
            'period_end.gte' => 'Periode akhir tidak boleh lebih kecil dari periode awal.',
            'data_provider_id.exists' => 'Penyedia data yang dipilih tidak tersedia.',
        ]);
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'dataset';
        $slug = $base;
        $counter = 2;

        while (Dataset::where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
