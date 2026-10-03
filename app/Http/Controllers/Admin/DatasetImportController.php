<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Models\DatasetObservation;
use App\Models\DatasetVariable;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DatasetImportController extends Controller
{
    private const REQUIRED_HEADERS = [
        'period',
        'value',
    ];

    private const HEADER_ALIASES = [
        'variable_code' => 'variable_code',
        'kode_variabel' => 'variable_code',
        'kode_variable' => 'variable_code',
        'variabel' => 'variable_code',
        'variable' => 'variable_code',

        'geography_code' => 'geography_code',
        'kode_wilayah' => 'geography_code',
        'kode_daerah' => 'geography_code',
        'region_code' => 'geography_code',
        'entity_code' => 'geography_code',

        'geography_name' => 'geography_name',
        'wilayah' => 'geography_name',
        'nama_wilayah' => 'geography_name',
        'daerah' => 'geography_name',
        'nama_daerah' => 'geography_name',
        'region' => 'geography_name',
        'region_name' => 'geography_name',
        'entity' => 'geography_name',
        'entity_name' => 'geography_name',

        'period' => 'period',
        'periode' => 'period',
        'tahun' => 'period',
        'year' => 'period',

        'value' => 'value',
        'nilai' => 'value',
        'jumlah' => 'value',
        'total' => 'value',

        'source_reference' => 'source_reference',
        'sumber' => 'source_reference',
        'referensi' => 'source_reference',
        'source' => 'source_reference',
        'url_sumber' => 'source_reference',

        'quality_status' => 'quality_status',
        'status_kualitas' => 'quality_status',
    ];

    public function create(Dataset $dataset): View
    {
        $dataset->loadCount([
            'variables',
            'observations',
            'variables as active_variables_count' => fn ($query) => $query
                ->where('is_active', true),
        ]);

        $activeVariables = $dataset->variables()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'admin.datasets.import',
            compact('dataset', 'activeVariables')
        );
    }

    public function store(
        Request $request,
        Dataset $dataset
    ): RedirectResponse {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:'.config(
                    'bacadulu.catalog.import_max_kilobytes'
                ),
            ],
            'variable_source' => [
                'nullable',
                'string',
                'max:120',
            ],
        ]);

        $activeVariables = $dataset->variables()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($activeVariables->isEmpty()) {
            return back()->with(
                'error',
                'Aktifkan minimal satu variabel sebelum mengimpor observasi.'
            );
        }

        $targetVariable = $this->resolveTargetVariable(
            $request,
            $activeVariables
        );

        $variablesByCode = $activeVariables->keyBy(
            fn (DatasetVariable $variable) => Str::lower(
                trim($variable->code)
            )
        );

        $file = $request->file('file');

        $extension = Str::lower(
            (string) $file->getClientOriginalExtension()
        );

        if (! in_array($extension, ['csv', 'txt'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Ekstensi file harus .csv atau .txt.',
            ]);
        }

        $path = $file->getRealPath();

        $probe = file_get_contents(
            $path,
            false,
            null,
            0,
            4096
        );

        if (
            $probe === false
            || str_contains($probe, "\0")
        ) {
            throw ValidationException::withMessages([
                'file' => 'File terdeteksi sebagai biner atau tidak dapat dibaca.',
            ]);
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'File CSV tidak dapat dibaca.',
            ]);
        }

        $firstLine = fgets($handle);

        rewind($handle);

        $delimiter = substr_count(
            (string) $firstLine,
            ';'
        ) > substr_count(
            (string) $firstLine,
            ','
        )
            ? ';'
            : ',';

        $rawHeaders = fgetcsv(
            $handle,
            0,
            $delimiter,
            '"',
            ''
        ) ?: [];

        $headers = array_map(
            fn ($value) => $this->normalizeHeader(
                (string) $value
            ),
            $rawHeaders
        );

        if (in_array('', $headers, true)) {
            fclose($handle);

            throw ValidationException::withMessages([
                'file' => 'Ada nama kolom CSV yang kosong.',
            ]);
        }

        if (
            count($headers)
            !== count(array_unique($headers))
        ) {
            fclose($handle);

            throw ValidationException::withMessages([
                'file' => 'Ada kolom CSV yang terbaca sebagai kolom yang sama. Gunakan satu nama untuk setiap kolom.',
            ]);
        }

        $missing = array_diff(
            self::REQUIRED_HEADERS,
            $headers
        );

        if ($missing !== []) {
            fclose($handle);

            throw ValidationException::withMessages([
                'file' => 'CSV minimal harus memiliki kolom tahun/periode dan nilai/value.',
            ]);
        }

        if (
            $targetVariable === null
            && ! in_array(
                'variable_code',
                $headers,
                true
            )
        ) {
            fclose($handle);

            throw ValidationException::withMessages([
                'variable_source' => 'Mode banyak variabel membutuhkan kolom variable_code atau kode_variabel di dalam CSV.',
            ]);
        }

        $imported = 0;
        $skipped = 0;
        $emptyRows = 0;
        $processedRows = 0;
        $skipReasons = [];
        $skipExamples = [];
        $line = 1;

        $limit = (int) config(
            'bacadulu.catalog.import_limit',
            50000
        );

        DB::beginTransaction();

        try {
            while (
                ($values = fgetcsv(
                    $handle,
                    0,
                    $delimiter,
                    '"',
                    ''
                )) !== false
            ) {
                $line++;
                $processedRows++;

                if ($processedRows > $limit) {
                    throw ValidationException::withMessages([
                        'file' => "Batas impor {$limit} baris terlampaui.",
                    ]);
                }

                $values = array_pad(
                    array_slice(
                        $values,
                        0,
                        count($headers)
                    ),
                    count($headers),
                    null
                );

                $row = array_combine(
                    $headers,
                    $values
                );

                if (
                    $row === false
                    || $this->rowIsEmpty($row)
                ) {
                    $emptyRows++;
                    continue;
                }

                $this->assertRowLengths(
                    $row,
                    $line
                );

                $variable = $targetVariable;

                if ($variable === null) {
                    $variableCode = trim(
                        (string) (
                            $row['variable_code'] ?? ''
                        )
                    );

                    if ($variableCode === '') {
                        $skipped++;

                        $this->recordSkip(
                            $skipReasons,
                            $skipExamples,
                            'kode variabel kosong',
                            'kode variabel kosong',
                            $line
                        );

                        continue;
                    }

                    $variable = $variablesByCode->get(
                        Str::lower($variableCode)
                    );

                    if (! $variable) {
                        $skipped++;

                        $this->recordSkip(
                            $skipReasons,
                            $skipExamples,
                            'kode variabel tidak ditemukan atau tidak aktif',
                            "kode variabel '{$variableCode}' tidak ditemukan atau tidak aktif",
                            $line
                        );

                        continue;
                    }
                }

                $period = trim(
                    (string) ($row['period'] ?? '')
                );

                $value = trim(
                    (string) ($row['value'] ?? '')
                );

                if ($period === '') {
                    $skipped++;

                    $this->recordSkip(
                        $skipReasons,
                        $skipExamples,
                        'tahun atau periode kosong',
                        'tahun atau periode kosong',
                        $line
                    );

                    continue;
                }

                if ($value === '') {
                    $skipped++;

                    $this->recordSkip(
                        $skipReasons,
                        $skipExamples,
                        'nilai kosong',
                        'nilai kosong',
                        $line
                    );

                    continue;
                }

                [$numeric, $text] = $this->parseValue(
                    $value
                );

                [
                    $geographyCode,
                    $geographyName,
                ] = $this->resolveGeography($row);

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
                        'source_reference' => trim(
                            (string) (
                                $row['source_reference']
                                ?? ''
                            )
                        ) ?: null,
                        'quality_status' => $this->qualityStatus(
                            (string) (
                                $row['quality_status']
                                ?? 'unreviewed'
                            )
                        ),
                    ]
                );

                $imported++;
            }

            $wasPublished = $imported > 0
                && $dataset->status === 'published';

            if ($imported > 0) {
                $dataset->update([
                    'last_updated_at' => now(),
                    'status' => $wasPublished
                        ? 'draft'
                        : $dataset->status,
                    'published_at' => $wasPublished
                        ? null
                        : $dataset->published_at,
                ]);
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            fclose($handle);

            throw $exception;
        }

        fclose($handle);

        $report = [
            'imported' => $imported,
            'skipped' => $skipped,
            'empty_rows' => $emptyRows,
            'reasons' => $skipReasons,
            'examples' => $skipExamples,
            'target_variable' => $targetVariable?->name,
            'valid_variable_codes' => $activeVariables
                ->pluck('code')
                ->values()
                ->all(),
        ];

        AuditService::record(
            'dataset.observations_imported',
            $dataset,
            [
                'imported' => $imported,
                'skipped' => $skipped,
                'empty_rows' => $emptyRows,
                'skip_reasons' => $skipReasons,
                'target_variable_id' => $targetVariable?->id,
                'delimiter' => $delimiter,
                'publication_reset' => $wasPublished,
            ]
        );

        if ($imported === 0) {
            return redirect()
                ->route(
                    'admin.datasets.import.create',
                    $dataset
                )
                ->with(
                    'error',
                    'Tidak ada observasi yang masuk. Periksa laporan impor, lalu unggah ulang CSV.'
                )
                ->with(
                    'import_report',
                    $report
                );
        }

        $notice = $wasPublished
            ? ' Dataset dikembalikan ke draft agar data baru diperiksa sebelum dipublikasikan ulang.'
            : '';

        return redirect()
            ->route(
                'admin.datasets.observations.index',
                [
                    $dataset,
                    'quality' => 'unreviewed',
                    'source' => 'csv',
                ]
            )
            ->with(
                'success',
                "Impor selesai: {$imported} baris masuk, {$skipped} baris dilewati.{$notice}"
            )
            ->with(
                'import_report',
                $report
            )
            ->with(
                'admin_next_step',
                [
                    'title' => 'Data sudah masuk ke antrean pemeriksaan',
                    'description' => 'Cocokkan nilainya dengan sumber, lalu ubah status menjadi Reviewed atau Verified.',
                    'label' => 'Mulai periksa data',
                    'url' => route(
                        'admin.datasets.observations.index',
                        [
                            $dataset,
                            'quality' => 'unreviewed',
                        ]
                    ),
                    'nav' => 'quality',
                ]
            );
    }

    private function resolveTargetVariable(
        Request $request,
        Collection $activeVariables
    ): ?DatasetVariable {
        if ($activeVariables->count() === 1) {
            return $activeVariables->first();
        }

        $source = trim(
            (string) $request->input(
                'variable_source',
                ''
            )
        );

        if ($source === 'csv') {
            return null;
        }

        if (ctype_digit($source)) {
            $variable = $activeVariables->firstWhere(
                'id',
                (int) $source
            );

            if ($variable) {
                return $variable;
            }
        }

        throw ValidationException::withMessages([
            'variable_source' => 'Pilih variabel tujuan untuk data CSV ini.',
        ]);
    }

    private function normalizeHeader(
        string $header
    ): string {
        $normalized = Str::of($header)
            ->replace("\xEF\xBB\xBF", '')
            ->trim()
            ->lower()
            ->replace([' ', '-'], '_')
            ->toString();

        return self::HEADER_ALIASES[$normalized]
            ?? $normalized;
    }

    private function rowIsEmpty(
        array $row
    ): bool {
        return collect($row)
            ->filter(
                fn ($value) => trim(
                    (string) $value
                ) !== ''
            )
            ->isEmpty();
    }

    private function resolveGeography(
        array $row
    ): array {
        $code = trim(
            (string) (
                $row['geography_code'] ?? ''
            )
        );

        $name = trim(
            (string) (
                $row['geography_name'] ?? ''
            )
        );

        if ($code === '' && $name === '') {
            return [
                'national',
                'Indonesia',
            ];
        }

        if ($name === '') {
            $name = $code;
        }

        if ($code === '') {
            $code = Str::slug($name);
        }

        return [
            $code,
            $name,
        ];
    }

    private function parseValue(
        string $value
    ): array {
        $candidate = trim($value);

        if (
            preg_match(
                '/^-?\d+(?:[.,]\d+)?$/',
                $candidate
            ) === 1
        ) {
            return [
                (float) str_replace(
                    ',',
                    '.',
                    $candidate
                ),
                null,
            ];
        }

        return [
            null,
            $candidate,
        ];
    }

    private function assertRowLengths(
        array $row,
        int $line
    ): void {
        $limits = [
            'variable_code' => 100,
            'geography_code' => 100,
            'geography_name' => 255,
            'period' => 30,
            'value' => 10000,
            'source_reference' => 255,
            'quality_status' => 30,
        ];

        foreach ($limits as $column => $limit) {
            if (
                mb_strlen(
                    (string) (
                        $row[$column] ?? ''
                    )
                ) > $limit
            ) {
                throw ValidationException::withMessages([
                    'file' => "Baris {$line}: kolom {$column} melebihi {$limit} karakter.",
                ]);
            }
        }
    }

    private function qualityStatus(
        string $status
    ): string {
        $status = Str::lower(
            trim($status)
        );

        return in_array(
            $status,
            [
                'unreviewed',
                'reviewed',
                'verified',
                'flagged',
            ],
            true
        )
            ? $status
            : 'unreviewed';
    }

    private function recordSkip(
        array &$reasons,
        array &$examples,
        string $reason,
        string $example,
        int $line
    ): void {
        $reasons[$reason] = (
            $reasons[$reason] ?? 0
        ) + 1;

        if (count($examples) < 10) {
            $examples[] = "Baris {$line}: {$example}.";
        }
    }
} 