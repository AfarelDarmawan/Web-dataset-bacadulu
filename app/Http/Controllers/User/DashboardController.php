<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DataAccessRequest;
use App\Models\DatasetVariable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $base = DataAccessRequest::where('user_id', $request->user()->id);

        return view('user.dashboard', [
            'stats' => [
                'requests' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'approved' => (clone $base)->where('status', 'approved')->count(),
                'available_variables' => DatasetVariable::where('is_active', true)
                    ->whereHas('dataset', fn ($query) => $query->published())
                    ->whereHas('observations', fn ($query) => $query->whereIn('quality_status', ['reviewed', 'verified']))
                    ->count(),
            ],
            'recentRequests' => (clone $base)->with('dataset.provider')->latest()->limit(6)->get(),
            'latestVariables' => DatasetVariable::where('is_active', true)
                ->whereHas('dataset', fn ($query) => $query->published())
                ->whereHas('observations', fn ($query) => $query->whereIn('quality_status', ['reviewed', 'verified']))
                ->with('dataset.provider')
                ->latest()
                ->limit(4)
                ->get(),
        ]);
    }
}
