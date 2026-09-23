<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataProvider;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DataProviderController extends Controller
{
    public function index(Request $request): View
    {
        $providers = DataProvider::query()
            ->withCount('datasets')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q')->trim().'%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.providers.index', compact('providers'));
    }

    public function create(): View
    {
        return view('admin.providers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProvider($request);
        $validated['slug'] = $this->uniqueSlug($validated['name']);
        $validated['created_by'] = $request->user()->id;

        $provider = DataProvider::create($validated);
        AuditService::record('provider.created', $provider);

        return redirect()->route('admin.providers.index')
            ->with('success', 'Provider data berhasil ditambahkan.')
            ->with('admin_next_step', [
                'title' => 'Sumber data sudah siap',
                'description' => 'Buat koleksi untuk mengelompokkan variabel dan observasi dari sumber ini.',
                'label' => 'Buat koleksi',
                'url' => route('admin.datasets.create'),
                'nav' => 'datasets',
            ]);
    }

    public function edit(DataProvider $provider): View
    {
        return view('admin.providers.edit', compact('provider'));
    }

    public function update(Request $request, DataProvider $provider): RedirectResponse
    {
        $validated = $this->validateProvider($request);
        if ($provider->name !== $validated['name']) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $provider->id);
        }

        $provider->update($validated);
        AuditService::record('provider.updated', $provider);

        return redirect()->route('admin.providers.index')->with('success', 'Provider data berhasil diperbarui.');
    }

    public function destroy(DataProvider $provider): RedirectResponse
    {
        if ($provider->datasets()->exists()) {
            return back()->with('error', 'Provider tidak dapat dihapus karena masih memiliki dataset.');
        }

        AuditService::record('provider.deleted', $provider, ['name' => $provider->name]);
        $provider->delete();

        return back()->with('success', 'Provider data berhasil dihapus.');
    }

    private function validateProvider(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'type' => ['required', Rule::in(['government', 'university', 'company', 'ngo', 'institution', 'other'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:190'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'provider';
        $slug = $base;
        $counter = 2;

        while (DataProvider::where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
