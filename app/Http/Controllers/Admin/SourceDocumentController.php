<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Models\SourceDocument;
use App\Services\Ai\ExtractionException;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SourceDocumentController extends Controller
{
    private const EXTENSIONS = ['pdf', 'csv', 'txt', 'xls', 'xlsx'];

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dataset_id' => ['required', 'integer', 'exists:datasets,id'],
            'file' => ['required', 'file', 'mimes:pdf,csv,txt,xls,xlsx', 'max:'.config('bacadulu.ai.max_kilobytes')],
        ]);

        $dataset = Dataset::findOrFail($validated['dataset_id']);
        $file = $request->file('file');
        $extension = Str::lower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, self::EXTENSIONS, true)) {
            throw ValidationException::withMessages(['file' => 'Ekstensi yang diizinkan: PDF, CSV, TXT, XLS, atau XLSX.']);
        }

        $path = $file->getRealPath();
        if (! is_string($path) || ! is_readable($path)) {
            throw ValidationException::withMessages(['file' => 'File upload tidak dapat dibaca.']);
        }
        $this->assertSignature($path, $extension);

        $hash = hash_file('sha256', $path);
        if (! is_string($hash)) {
            throw ValidationException::withMessages(['file' => 'Checksum dokumen tidak dapat dibuat.']);
        }

        $duplicate = SourceDocument::where('dataset_id', $dataset->id)->where('sha256', $hash)->first();
        if ($duplicate) {
            return redirect()->route('admin.source-documents.show', $duplicate)
                ->with('error', 'Dokumen identik sudah pernah diunggah ke dataset ini.');
        }

        $originalName = Str::of(basename((string) $file->getClientOriginalName()))
            ->replaceMatches('/[\x00-\x1F\x7F]/u', '')
            ->limit(220, '')
            ->toString();
        $storedName = (string) Str::uuid().'.'.$extension;
        $disk = (string) config('bacadulu.ai.disk', 'local');
        $storedPath = Storage::disk($disk)->putFileAs('source-documents/'.$dataset->id, $file, $storedName);
        if (! is_string($storedPath)) {
            throw ValidationException::withMessages(['file' => 'Dokumen gagal disimpan pada private storage.']);
        }

        try {
            $document = SourceDocument::create([
                'dataset_id' => $dataset->id,
                'uploaded_by' => $request->user()->id,
                'original_name' => $originalName ?: 'source.'.$extension,
                'storage_path' => $storedPath,
                'disk' => $disk,
                'mime_type' => $this->mimeForExtension($extension),
                'extension' => $extension,
                'size_bytes' => $file->getSize(),
                'sha256' => $hash,
                'status' => 'uploaded',
                'metadata' => ['client_mime' => $file->getClientMimeType()],
            ]);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($storedPath);
            throw $exception;
        }

        AuditService::record('source_document.uploaded', $document, [
            'dataset_id' => $dataset->id,
            'extension' => $extension,
            'size_bytes' => $document->size_bytes,
        ]);

        return redirect()->route('admin.source-documents.show', $document)
            ->with('success', 'Dokumen tersimpan privat. Jalankan ekstraksi setelah memeriksa tujuan dataset.');
    }

    public function show(SourceDocument $sourceDocument): View
    {
        $sourceDocument->load(['dataset.provider', 'uploader']);
        $jobs = $sourceDocument->extractionJobs()
            ->with(['requester', 'reviewer'])
            ->withCount([
                'rows',
                'rows as proposed_rows_count' => fn ($query) => $query->where('status', 'proposed'),
                'rows as accepted_rows_count' => fn ($query) => $query->where('status', 'accepted'),
                'rows as rejected_rows_count' => fn ($query) => $query->where('status', 'rejected'),
                'rows as invalid_rows_count' => fn ($query) => $query->where('validation_status', 'invalid'),
            ])
            ->latest()
            ->get();

        return view('admin.ai.documents.show', compact('sourceDocument', 'jobs'));
    }

    public function download(SourceDocument $sourceDocument): StreamedResponse
    {
        abort_unless(Storage::disk($sourceDocument->disk)->exists($sourceDocument->storage_path), 404);
        AuditService::record('source_document.downloaded', $sourceDocument, ['dataset_id' => $sourceDocument->dataset_id]);

        return Storage::disk($sourceDocument->disk)->download(
            $sourceDocument->storage_path,
            $sourceDocument->original_name,
            ['Content-Type' => $sourceDocument->mime_type, 'X-Content-Type-Options' => 'nosniff']
        );
    }

    public function destroy(SourceDocument $sourceDocument): RedirectResponse
    {
        $datasetId = $sourceDocument->dataset_id;
        try {
            [$disk, $path] = DB::transaction(function () use ($sourceDocument): array {
                $locked = SourceDocument::query()->lockForUpdate()->findOrFail($sourceDocument->id);
                if ($locked->extractionJobs()->where('status', 'approved')->exists()) {
                    throw new ExtractionException('Dokumen yang sudah menghasilkan observasi tidak boleh dihapus agar jejak sumber tetap utuh.', 'approved_source_locked');
                }
                if ($locked->extractionJobs()->whereIn('status', ['queued', 'processing', 'review'])->exists()) {
                    throw new ExtractionException('Selesaikan atau tolak ekstraksi yang masih aktif sebelum menghapus dokumen.', 'active_extraction_exists');
                }

                AuditService::record('source_document.deleted', $locked, [
                    'dataset_id' => $locked->dataset_id,
                    'original_name' => $locked->original_name,
                    'sha256' => $locked->sha256,
                ]);
                $file = [$locked->disk, $locked->storage_path];
                $locked->delete();

                return $file;
            });
        } catch (ExtractionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if (! Storage::disk($disk)->delete($path)) {
            Log::warning('Deleted source document record but private file cleanup failed.', ['disk' => $disk, 'path' => $path]);
        }

        return redirect()->route('admin.ai.index', ['dataset' => $datasetId])->with('success', 'Dokumen sumber dan staging terkait telah dihapus.');
    }

    private function assertSignature(string $path, string $extension): void
    {
        $handle = @fopen($path, 'rb');
        $prefix = $handle ? fread($handle, 8) : false;
        if (is_resource($handle)) {
            fclose($handle);
        }
        if (! is_string($prefix)) {
            throw ValidationException::withMessages(['file' => 'Signature file tidak dapat dibaca.']);
        }

        $valid = match ($extension) {
            'pdf' => str_starts_with($prefix, '%PDF-'),
            'xlsx' => str_starts_with($prefix, "PK\x03\x04"),
            'xls' => str_starts_with($prefix, "\xD0\xCF\x11\xE0"),
            'csv', 'txt' => ! str_contains($prefix, "\0"),
            default => false,
        };

        if (! $valid) {
            throw ValidationException::withMessages(['file' => 'Isi file tidak sesuai dengan ekstensi atau terdeteksi sebagai file biner yang tidak didukung.']);
        }
    }

    private function mimeForExtension(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
    }
}