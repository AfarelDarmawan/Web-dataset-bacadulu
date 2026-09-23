<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\DataProvider;
use App\Models\Dataset;
use App\Models\DatasetObservation;
use App\Models\DatasetVariable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DatasetCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $scope = (string) $request->string('scope');

        $variables = DatasetVariable::query()
            ->select('dataset_variables.*')
            ->where('is_active', true)
            ->whereHas('dataset', fn ($query) => $query->published())
            ->whereHas('observations', fn ($query) => $query->whereIn('quality_status', ['reviewed', 'verified']))
            ->with('dataset.provider')
            ->withCount([
                'observations as observations_count' => fn ($query) => $query->whereIn('quality_status', ['reviewed', 'verified']),
            ])
            ->addSelect([
                'entities_count' => DatasetObservation::query()
                    ->selectRaw('COUNT(DISTINCT geography_code)')
                    ->whereColumn('dataset_variable_id', 'dataset_variables.id')
                    ->whereIn('quality_status', ['reviewed', 'verified']),
                'first_observation_period' => DatasetObservation::query()
                    ->selectRaw('MIN(period)')
                    ->whereColumn('dataset_variable_id', 'dataset_variables.id')
                    ->whereIn('quality_status', ['reviewed', 'verified']),
                'last_observation_period' => DatasetObservation::query()
                    ->selectRaw('MAX(period)')
                    ->whereColumn('dataset_variable_id', 'dataset_variables.id')
                    ->whereIn('quality_status', ['reviewed', 'verified']),
            ])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.(string) $request->string('q')->trim()->limit(100, '').'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('dataset_variables.name', 'like', $term)
                        ->orWhere('dataset_variables.code', 'like', $term)
                        ->orWhere('dataset_variables.definition', 'like', $term)
                        ->orWhere('dataset_variables.category', 'like', $term)
                        ->orWhereHas('dataset', fn ($dataset) => $dataset
                            ->where('title', 'like', $term)
                            ->orWhere('code', 'like', $term)
                            ->orWhere('category', 'like', $term)
                            ->orWhereHas('provider', fn ($provider) => $provider->where('name', 'like', $term)));
                });
            })
            ->when(in_array($scope, ['regional', 'corporate'], true), fn ($query) => $query
                ->whereHas('dataset', fn ($dataset) => $dataset->where('data_scope', $scope)))
            ->when($request->filled('category'), fn ($query) => $query
                ->whereHas('dataset', fn ($dataset) => $dataset->where('category', $request->string('category'))))
            ->when($request->filled('provider'), fn ($query) => $query
                ->whereHas('dataset', fn ($dataset) => $dataset->where('data_provider_id', $request->integer('provider'))))
            ->when($request->filled('access'), fn ($query) => $query
                ->whereHas('dataset', fn ($dataset) => $dataset->where('access_type', $request->string('access'))))
            ->when($request->filled('period'), fn ($query) => $query
                ->whereHas('observations', fn ($observations) => $observations
                    ->whereIn('quality_status', ['reviewed', 'verified'])
                    ->where('period', $request->string('period'))))
            ->when($request->filled('entity'), function ($query) use ($request): void {
                $term = '%'.(string) $request->string('entity')->trim()->limit(100, '').'%';
                $query->whereHas('observations', fn ($observations) => $observations
                    ->whereIn('quality_status', ['reviewed', 'verified'])
                    ->where(fn ($entity) => $entity
                        ->where('geography_name', 'like', $term)
                        ->orWhere('geography_code', 'like', $term)));
            })
            ->orderByDesc('dataset_variables.updated_at')
            ->orderBy('dataset_variables.name')
            ->paginate(20)
            ->withQueryString();

        return view('public.datasets.index', [
            'variables' => $variables,
            'categories' => Dataset::published()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'providers' => DataProvider::where('status', 'active')
                ->whereHas('datasets', fn ($query) => $query->published())
                ->orderBy('name')
                ->get(),
            'periods' => DatasetObservation::query()
                ->whereIn('quality_status', ['reviewed', 'verified'])
                ->whereHas('variable', fn ($query) => $query
                    ->where('is_active', true)
                    ->whereHas('dataset', fn ($dataset) => $dataset->published()))
                ->select('period')
                ->distinct()
                ->orderByDesc('period')
                ->limit(200)
                ->pluck('period'),
        ]);
    }

    public function show(Request $request, Dataset $dataset): View
    {
        $dataset->load(['provider', 'variables' => fn ($query) => $query->where('is_active', true)->orderBy('name')]);
        abort_unless($dataset->isPubliclyAvailable(), 404);
        $dataset->loadCount([
            'variables as variables_count' => fn ($query) => $query->where('is_active', true),
            'observations as observations_count' => fn ($query) => $query
                ->whereIn('quality_status', ['reviewed', 'verified'])
                ->whereHas('variable', fn ($variable) => $variable->where('is_active', true)),
        ]);

        $releasedObservations = DatasetObservation::query()
            ->where('dataset_id', $dataset->id)
            ->whereIn('quality_status', ['reviewed', 'verified'])
            ->whereHas('variable', fn ($query) => $query->where('is_active', true));

        $observations = (clone $releasedObservations)
            ->with('variable')
            ->when($request->filled('variable'), fn ($query) => $query->where('dataset_variable_id', $request->integer('variable')))
            ->when($request->filled('period'), fn ($query) => $query->where('period', $request->string('period')))
            ->when($request->filled('geography'), fn ($query) => $query->where('geography_code', $request->string('geography')))
            ->when(! $dataset->isOpen(), fn ($query) => $query->select([
                'id', 'dataset_id', 'dataset_variable_id', 'geography_code', 'geography_name', 'period', 'quality_status',
            ]))
            ->orderByDesc('period')
            ->limit(config('bacadulu.catalog.preview_limit'))
            ->get();

        return view('public.datasets.show', [
            'dataset' => $dataset,
            'observations' => $observations,
            'periods' => (clone $releasedObservations)->select('period')->distinct()->orderByDesc('period')->limit(200)->pluck('period'),
            'geographies' => (clone $releasedObservations)
                ->select('geography_code')
                ->selectRaw('MAX(geography_name) as geography_name')
                ->groupBy('geography_code')
                ->orderBy('geography_name')
                ->limit(500)
                ->get(),
        ]);
    }

    public function variable(Request $request, Dataset $dataset, DatasetVariable $variable): View
    {
        $dataset->loadMissing('provider');
        abort_unless($dataset->isPubliclyAvailable(), 404);
        abort_unless($variable->dataset_id === $dataset->id && $variable->is_active, 404);

        $releasedObservations = DatasetObservation::query()
            ->where('dataset_id', $dataset->id)
            ->where('dataset_variable_id', $variable->id)
            ->whereIn('quality_status', ['reviewed', 'verified']);

        $observations = (clone $releasedObservations)
            ->when($request->filled('period'), fn ($query) => $query->where('period', $request->string('period')))
            ->when($request->filled('entity'), fn ($query) => $query->where('geography_code', $request->string('entity')))
            ->when(! $dataset->isOpen(), fn ($query) => $query->select([
                'id', 'dataset_id', 'dataset_variable_id', 'geography_code', 'geography_name', 'period', 'quality_status',
            ]))
            ->orderByDesc('period')
            ->orderBy('geography_name')
            ->limit(config('bacadulu.catalog.preview_limit'))
            ->get();

        $entities = (clone $releasedObservations)
            ->select('geography_code')
            ->selectRaw('MAX(geography_name) as geography_name')
            ->groupBy('geography_code')
            ->orderBy('geography_name')
            ->limit(500)
            ->get();
        $periods = (clone $releasedObservations)->select('period')->distinct()->orderByDesc('period')->limit(200)->pluck('period');

        return view('public.variables.show', [
            'dataset' => $dataset,
            'variable' => $variable,
            'observations' => $observations,
            'entities' => $entities,
            'periods' => $periods,
            'observationCount' => (clone $releasedObservations)->count(),
            'entityCount' => (clone $releasedObservations)->distinct()->count('geography_code'),
            'relatedVariables' => $dataset->variables()
                ->where('is_active', true)
                ->where('id', '!=', $variable->id)
                ->whereHas('observations', fn ($query) => $query->whereIn('quality_status', ['reviewed', 'verified']))
                ->orderBy('name')
                ->limit(6)
                ->get(),
        ]);
    }
}
