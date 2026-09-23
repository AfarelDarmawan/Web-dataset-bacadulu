<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiExtractionJob;
use App\Models\Dataset;
use App\Models\SourceDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiIngestionController extends Controller
{
    public function index(Request $request): View
    {
        $selectedDataset = $request->integer('dataset') > 0
            ? Dataset::find($request->integer('dataset'))
            : null;
        $documents = SourceDocument::query()
            ->with(['dataset.provider', 'uploader'])
            ->withCount('extractionJobs')
            ->when($selectedDataset, fn ($query) => $query->where('dataset_id', $selectedDataset->id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.ai.index', [
            'documents' => $documents,
            'selectedDataset' => $selectedDataset,
            'datasets' => Dataset::with('provider')->orderBy('title')->get(),
            'recentJobs' => AiExtractionJob::with(['sourceDocument', 'dataset', 'requester'])
                ->when($selectedDataset, fn ($query) => $query->where('dataset_id', $selectedDataset->id))
                ->withCount(['rows', 'rows as proposed_rows_count' => fn ($query) => $query->where('status', 'proposed')])
                ->latest()
                ->limit(12)
                ->get(),
            'reviewCount' => AiExtractionJob::where('status', 'review')->when($selectedDataset, fn ($query) => $query->where('dataset_id', $selectedDataset->id))->count(),
        ]);
    }
}