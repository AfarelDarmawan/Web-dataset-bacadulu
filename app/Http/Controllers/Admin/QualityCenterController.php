<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiExtractionJob;
use App\Models\DataAccessRequest;
use App\Models\Dataset;
use App\Models\DatasetObservation;
use App\Models\DataSyncRun;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class QualityCenterController extends Controller
{
    public function __invoke(): View
    {
        $datasetIssues = Dataset::query()
            ->with('provider')
            ->withCount([
                'observations as unreviewed_count' => fn (Builder $query) => $query->where('quality_status', 'unreviewed'),
                'observations as flagged_count' => fn (Builder $query) => $query->where('quality_status', 'flagged'),
            ])
            ->whereHas('observations', fn (Builder $query) => $query->whereIn('quality_status', ['unreviewed', 'flagged']))
            ->orderByDesc('flagged_count')
            ->orderByDesc('unreviewed_count')
            ->limit(12)
            ->get();

        return view('admin.quality.index', [
            'counts' => [
                'sync_review' => DataSyncRun::where('status', 'review')->count(),
                'extraction_review' => AiExtractionJob::where('status', 'review')->count(),
                'unreviewed' => DatasetObservation::where('quality_status', 'unreviewed')->count(),
                'flagged' => DatasetObservation::where('quality_status', 'flagged')->count(),
                'drafts' => Dataset::where('status', 'draft')->count(),
                'pending_requests' => DataAccessRequest::where('status', 'pending')->count(),
            ],
            'datasetIssues' => $datasetIssues,
            'reviewSyncs' => DataSyncRun::with(['connector.dataset', 'connector.variable'])
                ->where('status', 'review')
                ->oldest()
                ->limit(6)
                ->get(),
            'reviewExtractions' => AiExtractionJob::with(['sourceDocument', 'dataset'])
                ->withCount('rows')
                ->where('status', 'review')
                ->oldest()
                ->limit(6)
                ->get(),
            'draftDatasets' => Dataset::with('provider')
                ->withCount(['variables', 'observations'])
                ->where('status', 'draft')
                ->latest('updated_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
