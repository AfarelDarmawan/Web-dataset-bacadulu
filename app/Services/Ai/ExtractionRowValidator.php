<?php

namespace App\Services\Ai;

use App\Models\Dataset;
use App\Models\DatasetVariable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ExtractionRowValidator
{
    /**
     * Normalize untrusted extractor output before it reaches the staging table.
     */
    public function normalize(array $input, Dataset $dataset, Collection $variables): array
    {
        $issues = [];
        $code = $this->variableCode((string) ($input['variable_code'] ?? ''));
        $name = $this->plainText((string) ($input['variable_name'] ?? ''), 255);
        $period = $this->plainText((string) ($input['period'] ?? ''), 30);
        $locator = $this->plainText((string) ($input['source_locator'] ?? ''), 255);
        $dataType = in_array(($input['data_type'] ?? null), ['numeric', 'text', 'percentage', 'currency', 'index'], true)
            ? (string) $input['data_type']
            : 'numeric';

        if ($code === '') {
            $issues[] = 'Kode variabel tidak ditemukan.';
        }
        if ($name === '') {
            $issues[] = 'Nama variabel tidak ditemukan.';
        }
        if ($period === '') {
            $issues[] = 'Periode tidak ditemukan.';
        }
        if ($locator === '') {
            $issues[] = 'Lokasi sumber (halaman, sheet, atau baris) wajib tersedia.';
        }

        [$numeric, $text] = $this->normalizeValue($input['value_numeric'] ?? null, $input['value_text'] ?? null);
        if ($numeric === null && $text === null) {
            $issues[] = 'Nilai observasi kosong atau tidak valid.';
        }

        /** @var DatasetVariable|null $matched */
        $matched = $variables->get(Str::lower($code));
        $warnings = [];
        if ($code !== '' && ! $matched) {
            $warnings[] = 'Kode belum ada pada kamus variabel; variabel baru akan dibuat hanya jika baris diterima.';
        } elseif ($matched && $name !== '' && Str::lower($matched->name) !== Str::lower($name)) {
            $warnings[] = 'Nama hasil ekstraksi berbeda dari nama variabel yang sudah ada.';
        }

        $validationStatus = $issues !== [] ? 'invalid' : ($warnings !== [] ? 'warning' : 'valid');

        return [
            'matched_variable_id' => $matched?->id,
            'variable_code' => $code,
            'variable_name' => $name ?: 'Belum teridentifikasi',
            'variable_definition' => $this->nullableText($input['variable_definition'] ?? null, 5000),
            'unit' => $this->nullableText($input['unit'] ?? null, 80),
            'data_type' => $dataType,
            'geography_code' => $this->plainText((string) ($input['geography_code'] ?? ''), 100) ?: 'national',
            'geography_name' => $this->plainText((string) ($input['geography_name'] ?? ''), 255) ?: 'Indonesia',
            'period' => $period,
            'value_numeric' => $numeric,
            'value_text' => $text,
            'source_locator' => $locator ?: 'Lokasi belum teridentifikasi',
            'source_excerpt' => $this->nullableText($input['source_excerpt'] ?? null, 2000),
            'confidence' => $this->confidence($input['confidence'] ?? 0),
            'validation_status' => $validationStatus,
            'validation_issues' => [...$issues, ...$warnings],
        ];
    }

    private function variableCode(string $value): string
    {
        return Str::of($value)
            ->trim()
            ->upper()
            ->replaceMatches('/[^A-Z0-9._-]+/', '_')
            ->trim('_')
            ->substr(0, 100)
            ->toString();
    }

    private function plainText(string $value, int $limit): string
    {
        return Str::of($value)->replaceMatches('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ')->squish()->limit($limit, '')->toString();
    }

    private function nullableText(mixed $value, int $limit): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = $this->plainText((string) $value, $limit);

        return $text === '' ? null : $text;
    }

    private function normalizeValue(mixed $numeric, mixed $text): array
    {
        if (is_int($numeric) || is_float($numeric) || (is_string($numeric) && preg_match('/^-?\d+(?:\.\d+)?$/', trim($numeric)) === 1)) {
            $number = (float) $numeric;
            if (is_finite($number) && abs($number) < 1e18) {
                return [round($number, 6), null];
            }
        }

        if (is_scalar($text)) {
            $clean = $this->plainText((string) $text, 10000);
            if ($clean !== '') {
                return [null, $clean];
            }
        }

        return [null, null];
    }

    private function confidence(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0;
        }

        return round(max(0, min(1, (float) $value)), 4);
    }
}
