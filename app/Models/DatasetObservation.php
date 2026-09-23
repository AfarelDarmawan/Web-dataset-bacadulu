<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatasetObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'dataset_variable_id',
        'geography_code',
        'geography_name',
        'period',
        'value_numeric',
        'value_text',
        'source_reference',
        'quality_status',
    ];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'decimal:6',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function variable(): BelongsTo
    {
        return $this->belongsTo(DatasetVariable::class, 'dataset_variable_id');
    }

    public function displayValue(): string
    {
        if ($this->value_numeric !== null) {
            return rtrim(rtrim(number_format((float) $this->value_numeric, 6, '.', ','), '0'), '.');
        }

        return (string) $this->value_text;
    }
}
