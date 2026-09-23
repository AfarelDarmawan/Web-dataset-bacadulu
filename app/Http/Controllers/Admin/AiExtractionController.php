<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiExtractionJob;
use App\Models\AiExtractionRow;
use App\Models\SourceDocument;
use App\Services\Ai\DatasetExtractionApprovalService;
use App\Services\Ai\DatasetExtractionService;
use App\Services\Ai\ExtractionException;
use App\Services\Ai\ExtractionRowValidator;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiExtractionController extends Controller
{
    public function store(Request $request, SourceDocument $sourceDocument, DatasetExtractionService $service): RedirectResponse
    {
        if (! in_array($sourceDocument->extension, ['csv', 'txt'], true)) {
            $request->validate(['confirm_external_processing' => ['accepted']]);
        }

        try {
            $job = $service->run($sourceDocument, $request->user());
        } catch (ExtractionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.ai-extractions.show', $job)
            ->with('success', 'Ekstraksi selesai. Data masih berada di staging dan wajib direview.');
    }

    public function show(Request $request, AiExtractionJob $aiExtraction): View
    {
        $aiExtraction->load(['sourceDocument', 'dataset.provider', 'requester', 'reviewer']);
        $rows = $aiExtraction->rows()
            ->with(['matchedVariable', 'reviewer'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('validation'), fn ($query) => $query->where('validation_status', $request->string('validation')))
            ->orderBy('row_index')
            ->paginate(50)
            ->withQueryString();

        return view('admin.ai.jobs.show', [
            'job' => $aiExtraction,
            'rows' => $rows,
            'counts' => $aiExtraction->rows()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'validationCounts' => $aiExtraction->rows()
                ->selectRaw('validation_status, COUNT(*) as total')
                ->groupBy('validation_status')
                ->pluck('total', 'validation_status'),
        ]);
    }

    public function editRow(AiExtractionJob $aiExtraction, AiExtractionRow $row): View
    {
        $this->assertRowBelongsToJob($aiExtraction, $row);
        abort_unless($aiExtraction->isReviewable(), 409);

        return view('admin.ai.rows.edit', [
            'job' => $aiExtraction->load(['dataset', 'sourceDocument']),
            'row' => $row,
        ]);
    }

    public function updateRow(
        Request $request,
        AiExtractionJob $aiExtraction,
        AiExtractionRow $row,
        ExtractionRowValidator $validator
    ): RedirectResponse {
        $this->assertRowBelongsToJob($aiExtraction, $row);
        abort_unless($aiExtraction->isReviewable(), 409);

        $validated = $request->validate([
            'variable_code' => ['required', 'string', 'max:100'],
            'variable_name' => ['required', 'string', 'max:255'],
            'variable_definition' => ['nullable', 'string', 'max:5000'],
            'unit' => ['nullable', 'string', 'max:80'],
            'data_type' => ['required', Rule::in(['numeric', 'text', 'percentage', 'currency', 'index'])],
            'geography_code' => ['required', 'string', 'max:100'],
            'geography_name' => ['required', 'string', 'max:255'],
            'period' => ['required', 'string', 'max:30'],
            'value_numeric' => ['nullable', 'numeric', 'required_without:value_text'],
            'value_text' => ['nullable', 'string', 'max:10000', 'required_without:value_numeric'],
            'source_locator' => ['required', 'string', 'max:255'],
            'source_excerpt' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['proposed', 'accepted', 'rejected'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);
        if (filled($validated['value_numeric'] ?? null) && filled($validated['value_text'] ?? null)) {
            throw ValidationException::withMessages(['value_text' => 'Isi hanya satu jenis nilai: numerik atau teks.']);
        }

        $dataset = $aiExtraction->dataset;
        $variables = $dataset->variables()->get()->keyBy(fn ($variable) => Str::lower($variable->code));
        $clean = $validator->normalize($validated + ['confidence' => $row->confidence], $dataset, $variables);
        if ($validated['status'] === 'accepted' && $clean['validation_status'] === 'invalid') {
            throw ValidationException::withMessages(['status' => 'Baris tidak valid tidak dapat diterima. Lengkapi data sumber terlebih dahulu.']);
        }

        $row = DB::transaction(function () use ($aiExtraction, $row, $clean, $validated, $request): AiExtractionRow {
            $lockedJob = AiExtractionJob::query()->lockForUpdate()->findOrFail($aiExtraction->id);
            abort_unless($lockedJob->isReviewable(), 409);
            $lockedRow = $lockedJob->rows()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $lockedRow->update($clean + [
                'status' => $validated['status'],
                'admin_note' => $validated['admin_note'] ?? null,
                'reviewed_by' => $validated['status'] === 'proposed' ? null : $request->user()->id,
                'reviewed_at' => $validated['status'] === 'proposed' ? null : now(),
            ]);

            return $lockedRow;
        });
        AuditService::record('ai_extraction.row_reviewed', $row, [
            'job_id' => $aiExtraction->id,
            'status' => $row->status,
            'validation_status' => $row->validation_status,
        ]);

        return redirect()->route('admin.ai-extractions.show', $aiExtraction)
            ->with('success', 'Kandidat baris diperbarui.');
    }

    public function bulkRows(Request $request, AiExtractionJob $aiExtraction): RedirectResponse
    {
        abort_unless($aiExtraction->isReviewable(), 409);
        $validated = $request->validate([
            'action' => ['required', Rule::in(['accept_selected', 'reject_selected', 'accept_all_valid', 'reject_all_invalid'])],
            'row_ids' => ['nullable', 'array', 'max:1000'],
            'row_ids.*' => ['integer', 'distinct'],
        ]);

        $accept = str_starts_with($validated['action'], 'accept_');
        $count = DB::transaction(function () use ($aiExtraction, $validated, $accept, $request): int {
            $lockedJob = AiExtractionJob::query()->lockForUpdate()->findOrFail($aiExtraction->id);
            abort_unless($lockedJob->isReviewable(), 409);
            $query = $lockedJob->rows()->where('status', 'proposed');
            if (str_ends_with($validated['action'], '_selected')) {
                $ids = $validated['row_ids'] ?? [];
                if ($ids === []) {
                    throw ValidationException::withMessages(['row_ids' => 'Pilih minimal satu baris.']);
                }
                $query->whereIn('id', $ids);
                if ((clone $query)->lockForUpdate()->count() !== count($ids)) {
                    abort(404);
                }
            } elseif ($validated['action'] === 'accept_all_valid') {
                $query->where('validation_status', '!=', 'invalid');
            } else {
                $query->where('validation_status', 'invalid');
            }

            if ($accept && (clone $query)->where('validation_status', 'invalid')->exists()) {
                throw ValidationException::withMessages(['row_ids' => 'Baris invalid tidak dapat diterima. Edit atau tolak baris tersebut.']);
            }

            return $query->update([
                'status' => $accept ? 'accepted' : 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
        });
        AuditService::record('ai_extraction.rows_bulk_reviewed', $aiExtraction, [
            'decision' => $accept ? 'accepted' : 'rejected',
            'rows' => $count,
        ]);

        return back()->with('success', "{$count} kandidat berhasil ".($accept ? 'diterima' : 'ditolak').'.');
    }

    public function approve(
        Request $request,
        AiExtractionJob $aiExtraction,
        DatasetExtractionApprovalService $service
    ): RedirectResponse {
        try {
            $result = $service->approve($aiExtraction, $request->user());
        } catch (ExtractionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = "Finalisasi selesai: {$result['observations_created']} observasi dibuat, {$result['observations_updated']} diperbarui, dan {$result['variables_created']} variabel baru dibuat.";
        if ($result['publication_reset']) {
            $message .= ' Dataset dikembalikan ke draft untuk quality control.';
        }

        return redirect()->route('admin.datasets.observations.index', $aiExtraction->dataset_id)->with('success', $message);
    }

    public function reject(
        Request $request,
        AiExtractionJob $aiExtraction,
        DatasetExtractionApprovalService $service
    ): RedirectResponse {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        try {
            $service->reject($aiExtraction, $request->user(), $validated['reason'] ?? null);
        } catch (ExtractionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.source-documents.show', $aiExtraction->source_document_id)
            ->with('success', 'Ekstraksi ditolak. Tidak ada observasi yang dimasukkan ke dataset.');
    }

    public function recover(AiExtractionJob $aiExtraction): RedirectResponse
    {
        try {
            $job = DB::transaction(function () use ($aiExtraction): AiExtractionJob {
                $locked = AiExtractionJob::query()->lockForUpdate()->findOrFail($aiExtraction->id);
                if (! in_array($locked->status, ['queued', 'processing'], true)) {
                    throw new ExtractionException('Hanya proses yang masih berstatus antre atau berjalan yang dapat dipulihkan.', 'job_not_recoverable');
                }
                if (! $locked->isStale()) {
                    throw new ExtractionException('Proses ini belum melewati batas waktu aman. Muat ulang halaman beberapa saat lagi.', 'job_not_stale');
                }

                $locked->update([
                    'status' => 'failed',
                    'error_code' => 'stale_process_recovered',
                    'error_message' => 'Proses berhenti tanpa menyelesaikan respons dan dipulihkan manual oleh administrator.',
                    'completed_at' => now(),
                ]);

                $hasOtherOpenJob = AiExtractionJob::query()
                    ->where('source_document_id', $locked->source_document_id)
                    ->where('id', '!=', $locked->id)
                    ->whereIn('status', ['queued', 'processing', 'review'])
                    ->exists();
                if (! $hasOtherOpenJob) {
                    SourceDocument::whereKey($locked->source_document_id)->update(['status' => 'uploaded']);
                }

                return $locked;
            });
        } catch (ExtractionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        AuditService::record('ai_extraction.stale_recovered', $job, [
            'source_document_id' => $job->source_document_id,
        ]);

        return redirect()->route('admin.source-documents.show', $job->source_document_id)
            ->with('success', 'Status proses macet sudah dipulihkan. Dokumen aman untuk diekstrak ulang.');
    }

    private function assertRowBelongsToJob(AiExtractionJob $job, AiExtractionRow $row): void
    {
        abort_unless($row->ai_extraction_job_id === $job->id, 404);
    }
}
