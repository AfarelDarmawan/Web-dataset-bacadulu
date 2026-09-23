<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataAccessRequest;
use App\Models\DataProvider;
use App\Models\AiExtractionJob;
use App\Models\Dataset;
use App\Models\DatasetObservation;
use App\Models\DatasetVariable;
use App\Models\DataSyncRun;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $reviewSyncs = DataSyncRun::where('status', 'review')->count();
        $reviewExtractions = AiExtractionJob::where('status', 'review')->count();
        $unreviewed = DatasetObservation::where('quality_status', 'unreviewed')->count();
        $flagged = DatasetObservation::where('quality_status', 'flagged')->count();
        $pendingRequests = DataAccessRequest::where('status', 'pending')->count();

        return view('admin.dashboard', [
            'stats' => [
                'providers' => DataProvider::count(),
                'active_providers' => DataProvider::where('status', 'active')->count(),
                'datasets' => Dataset::count(),
                'variables' => DatasetVariable::count(),
                'published' => Dataset::published()->count(),
                'drafts' => Dataset::where('status', 'draft')->count(),
                'observations' => DatasetObservation::count(),
                'unreviewed' => $unreviewed,
                'flagged' => $flagged,
                'pending_requests' => $pendingRequests,
                'review_extractions' => $reviewExtractions,
                'review_syncs' => $reviewSyncs,
                'attention_total' => $reviewSyncs + $reviewExtractions + $unreviewed + $flagged + $pendingRequests,
                'researchers' => User::where('role', 'researcher')->count(),
            ],
            'pendingRequests' => DataAccessRequest::with(['user', 'dataset'])->where('status', 'pending')->oldest()->limit(8)->get(),
            'recentDatasets' => Dataset::with('provider')->withCount(['variables', 'observations'])->latest()->limit(6)->get(),
        ]);
    }
}