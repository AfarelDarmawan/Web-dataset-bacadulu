<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataProvider;
use App\Models\DatasetVariable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VariableCatalogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = DatasetVariable::query()
            ->with(['dataset.provider'])
            ->withCount(['observations', 'dataConnectors'])
            ->when($request->filled('q'), function (Builder $builder) use ($request): void {
                $search = trim((string) $request->query('q'));
                $builder->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('definition', 'like', "%{$search}%")
                        ->orWhereHas('dataset', fn (Builder $dataset) => $dataset
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%"));
                });
            })
            ->when(in_array($request->query('scope'), ['corporate', 'regional'], true), fn (Builder $builder) => $builder
                ->whereHas('dataset', fn (Builder $dataset) => $dataset->where('data_scope', $request->query('scope'))))
            ->when(in_array($request->query('access'), ['open', 'standard', 'premium'], true), fn (Builder $builder) => $builder
                ->where('access_tier', $request->query('access')))
            ->when(in_array($request->query('status'), ['active', 'inactive'], true), fn (Builder $builder) => $builder
                ->where('is_active', $request->query('status') === 'active'))
            ->when($request->filled('provider'), fn (Builder $builder) => $builder
                ->whereHas('dataset', fn (Builder $dataset) => $dataset->where('data_provider_id', $request->integer('provider'))))
            ->orderBy('name');

        return view('admin.catalog.variables', [
            'variables' => $query->paginate(30)->withQueryString(),
            'providers' => DataProvider::query()->orderBy('name')->get(['id', 'name']),
            'totalVariables' => DatasetVariable::count(),
            'activeVariables' => DatasetVariable::where('is_active', true)->count(),
            'pricedVariables' => DatasetVariable::where('price_per_cell', '>', 0)->count(),
        ]);
    }
}
