<?php

namespace App\Services\Ai;

use App\Models\SourceDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LocalCsvStagingExtractor
{
    private const REQUIRED_HEADERS = ['variable_code', 'geography_code', 'geography_name', 'period', 'value'];

    public function extract(SourceDocument $document): array
    {
        $path = Storage::disk($document->disk)->path($document->storage_path);
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new ExtractionException('Dokumen CSV privat tidak dapat dibaca.', 'source_unreadable');
        }

        try {
            $probe = fread($handle, 4096);
            if ($probe === false || str_contains($probe, "\0") || ! mb_check_encoding($probe, 'UTF-8')) {
                throw new ExtractionException('File CSV harus berupa teks UTF-8 dan tidak boleh mengandung data biner.', 'invalid_csv');
            }

            rewind($handle);
            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = substr_count((string) $firstLine, ';') > substr_count((string) $firstLine, ',') ? ';' : ',';
            $headers = array_map(fn ($value) => $this->header((string) $value), fgetcsv($handle, 0, $delimiter, '"', '') ?: []);

            if ($headers === [] || count($headers) !== count(array_unique($headers))) {
                throw new ExtractionException('Header CSV kosong atau memiliki nama kolom duplikat.', 'invalid_csv_header');
            }

            $missing = array_diff(self::REQUIRED_HEADERS, $headers);
            if ($missing !== []) {
                throw new ExtractionException('Kolom CSV wajib belum lengkap: '.implode(', ', $missing).'.', 'invalid_csv_header');
            }

            $rows = [];
            $line = 1;
            $limit = (int) config('bacadulu.ai.max_rows', 1000);
            $variables = $document->dataset->variables->keyBy(fn ($variable) => Str::lower($variable->code));

            while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $line++;
                if (count($rows) >= $limit) {
                    throw new ExtractionException("Ekstraksi dibatasi {$limit} baris per proses. Gunakan impor CSV massal untuk file yang lebih besar.", 'row_limit');
                }

                $values = array_pad(array_slice($values, 0, count($headers)), count($headers), null);
                $row = array_combine($headers, $values);
                if ($row === false || collect($row)->every(fn ($value) => trim((string) $value) === '')) {
                    continue;
                }

                foreach ($row as $column => $value) {
                    if (str_contains((string) $value, "\0") || ! mb_check_encoding((string) $value, 'UTF-8')) {
                        throw new ExtractionException("Baris {$line}, kolom {$column}, bukan teks UTF-8 yang valid.", 'invalid_csv_value');
                    }
                    if (mb_strlen((string) $value) > 10000) {
                        throw new ExtractionException("Baris {$line}, kolom {$column}, terlalu panjang.", 'invalid_csv_value');
                    }
                }

                $code = trim((string) ($row['variable_code'] ?? ''));
                $known = $variables->get(Str::lower($code));
                [$numeric, $text] = $this->value((string) ($row['value'] ?? ''));

                $rows[] = [
                    'variable_code' => $code,
                    'variable_name' => trim((string) ($row['variable_name'] ?? '')) ?: ($known?->name ?? $code),
                    'variable_definition' => trim((string) ($row['variable_definition'] ?? '')) ?: $known?->definition,
                    'unit' => trim((string) ($row['unit'] ?? '')) ?: $known?->unit,
                    'data_type' => trim((string) ($row['data_type'] ?? '')) ?: ($known?->data_type ?? ($numeric !== null ? 'numeric' : 'text')),
                    'geography_code' => trim((string) ($row['geography_code'] ?? '')),
                    'geography_name' => trim((string) ($row['geography_name'] ?? '')),
                    'period' => trim((string) ($row['period'] ?? '')),
                    'value_numeric' => $numeric,
                    'value_text' => $text,
                    'source_locator' => 'Baris CSV '.$line,
                    'source_excerpt' => trim((string) ($row['source_reference'] ?? '')) ?: null,
                    'confidence' => 1,
                ];
            }
        } finally {
            fclose($handle);
        }

        if ($rows === []) {
            throw new ExtractionException('Tidak ada baris data yang dapat diproses dari CSV.', 'empty_extraction');
        }

        return [
            'summary' => count($rows).' kandidat observasi dibaca secara lokal dari CSV.',
            'confidence' => 1,
            'rows' => $rows,
            'response_id' => null,
            'usage' => ['mode' => 'local_csv'],
        ];
    }

    private function header(string $value): string
    {
        return Str::of($value)->replace("\xEF\xBB\xBF", '')->trim()->lower()->replace([' ', '-'], '_')->toString();
    }

    private function value(string $value): array
    {
        $candidate = trim($value);
        if (preg_match('/^-?\d+(?:[.,]\d+)?$/', $candidate) === 1) {
            return [(float) str_replace(',', '.', $candidate), null];
        }

        return [null, $candidate === '' ? null : $candidate];
    }
}
