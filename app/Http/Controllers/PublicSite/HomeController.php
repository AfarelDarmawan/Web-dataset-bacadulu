<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\DataProvider;
use App\Models\Dataset;
use App\Models\DatasetObservation;
use App\Models\DatasetVariable;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featuredVariables = DatasetVariable::query()
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
            ->latest('dataset_variables.updated_at')
            ->limit(8)
            ->get();

        return view('public.home', [
            'featuredVariables' => $featuredVariables,
            'stats' => [
                'datasets' => Dataset::published()->count(),
                'providers' => DataProvider::where('status', 'active')
                    ->whereHas('datasets', fn ($query) => $query->published())
                    ->count(),
                'variables' => DatasetVariable::where('is_active', true)->whereHas('dataset', fn ($query) => $query->published())->count(),
                'observations' => DatasetObservation::whereIn('quality_status', ['reviewed', 'verified'])
                    ->whereHas('variable', fn ($query) => $query->where('is_active', true))
                    ->whereHas('dataset', fn ($query) => $query->published())
                    ->count(),
            ],
        ]);
    }
}
