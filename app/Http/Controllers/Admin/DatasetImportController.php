<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Models\DatasetObservation;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DatasetImportController extends Controller
{
    private const REQUIRED_HEADERS = [
        'variable_code',
        'geography_code',
        'geography_name',
        'period',
        'value',
    ];

    public function create(Dataset $dataset): View
    {
        return view('admin.datasets.import', compact('dataset'));
    }

    public function store(Request $request, Dataset $dataset): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:'.config('bacadulu.catalog.import_max_kilobytes')],
        ]);

        $file = $request->file('file');
        $extension = Str::lower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'txt'], true)) {
            throw ValidationException::withMessages(['file' => 'Ekstensi file harus .csv atau .txt.']);
        }

        $path = $file->getRealPath();
        $probe = file_get_contents($path, false, null, 0, 4096);
        if ($probe === false || str_contains($probe, "\0")) {
            throw ValidationException::withMessages(['file' => 'File terdeteksi sebagai biner atau tidak dapat dibaca.']);
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'File CSV tidak dapat dibaca.']);
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = substr_count((string) $firstLine, ';') > substr_count((string) $firstLine, ',') ? ';' : ',';
        $rawHeaders = fgetcsv($handle, 0, $delimiter, '"', '') ?: [];
        $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), $rawHeaders);

        if (count($headers) !== count(array_unique($headers))) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'Header CSV tidak boleh memiliki nama kolom duplikat.']);
        }

        $missing = array_diff(self::REQUIRED_HEADERS, $headers);
        if ($missing !== []) {
            fclose($handle);
            throw ValidationException::withMessages([
                'file' => 'Kolom wajib belum lengkap: '.implode(', ', $missing).'.',
            ]);
        }

        $variables = $dataset->variables()->get()->keyBy(fn ($variable) => Str::lower($variable->code));
        if ($variables->isEmpty()) {
            fclose($handle);
            return back()->with('error', 'Tambahkan variabel sebelum mengimpor observasi.');
        }

        $imported = 0;
        $skipped = 0;
        $line = 1;
        $limit = (int) config('bacadulu.catalog.import_limit', 50000);

        DB::beginTransaction();

        try {
            while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $line++;
                if ($imported + $skipped >= $limit) {
                    throw ValidationException::withMessages([
                        'file' => "Batas impor {$limit} baris terlampaui.",
                    ]);
                }

                $values = array_pad(array_slice($values, 0, count($headers)), count($headers), null);
                $row = array_combine($headers, $values);

                if ($row === false || $this->rowIsEmpty($row)) {
                    $skipped++;
                    continue;
                }

                $this->assertRowLengths($row, $line);

                $variable = $variables->get(Str::lower(trim((string) ($row['variable_code'] ?? ''))));
                $period = trim((string) ($row['period'] ?? ''));
                $value = trim((string) ($row['value'] ?? ''));

                if (! $variable || $period === '' || $value === '') {
                    $skipped++;
                    continue;
                }

                [$numeric, $text] = $this->parseValue($value);
                $geographyName = trim((string) ($row['geography_name'] ?? '')) ?: 'Indonesia';
                $geographyCode = trim((string) ($row['geography_code'] ?? '')) ?: Str::slug($geographyName);

                DatasetObservation::updateOrCreate(
                    [
                        'dataset_id' => $dataset->id,
                        'dataset_variable_id' => $variable->id,
                        'geography_code' => $geographyCode,
                        'period' => $period,
                    ],
                    [
                        'geography_name' => $geographyName,
                        'value_numeric' => $numeric,
                        'value_text' => $text,
                        'source_reference' => trim((string) ($row['source_reference'] ?? '')) ?: null,
                        'quality_status' => $this->qualityStatus((string) ($row['quality_status'] ?? 'unreviewed')),
                    ]
                );

                $imported++;
            }

            $wasPublished = $dataset->status === 'published';
            $dataset->update([
                'last_updated_at' => now(),
                'status' => $wasPublished ? 'draft' : $dataset->status,
                'published_at' => $wasPublished ? null : $dataset->published_at,
            ]);
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            fclose($handle);
            throw $exception;
        }

        fclose($handle);
        AuditService::record('dataset.observations_imported', $dataset, [
            'imported' => $imported,
            'skipped' => $skipped,
            'delimiter' => $delimiter,
            'publication_reset' => $wasPublished,
        ]);

        $notice = $wasPublished
            ? ' Dataset dikembalikan ke draft agar data baru melalui quality control sebelum dipublikasikan ulang.'
            : '';

        return redirect()->route('admin.datasets.edit', $dataset)
            ->with('success', "Impor selesai: {$imported} baris diproses, {$skipped} baris dilewati.{$notice}")
            ->with('admin_next_step', $imported > 0 ? [
                'title' => 'Data sudah masuk',
                'description' => 'Periksa nilai dan status kualitas sebelum koleksi diterbitkan.',
                'label' => 'Periksa kualitas',
                'url' => route('admin.datasets.observations.index', $dataset),
                'nav' => 'quality',
            ] : null);
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)
            ->replace("\xEF\xBB\xBF", '')
            ->trim()
            ->lower()
            ->replace([' ', '-'], '_')
            ->toString();
    }

    private function rowIsEmpty(array $row): bool
    {
        return collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty();
    }

    private function parseValue(string $value): array
    {
        $candidate = trim($value);
        if (preg_match('/^-?\d+(?:[.,]\d+)?$/', $candidate) === 1) {
            return [(float) str_replace(',', '.', $candidate), null];
        }

        return [null, $candidate];
    }

    private function assertRowLengths(array $row, int $line): void
    {
        $limits = [
            'geography_code' => 100,
            'geography_name' => 255,
            'period' => 30,
            'value' => 10000,
            'source_reference' => 255,
            'quality_status' => 30,
        ];

        foreach ($limits as $column => $limit) {
            if (mb_strlen((string) ($row[$column] ?? '')) > $limit) {
                throw ValidationException::withMessages([
                    'file' => "Baris {$line}: kolom {$column} melebihi {$limit} karakter.",
                ]);
            }
        }
    }

    private function qualityStatus(string $status): string
    {
        $status = Str::lower(trim($status));

        return in_array($status, ['unreviewed', 'reviewed', 'verified', 'flagged'], true)
            ? $status
            : 'unreviewed';
    }
}
