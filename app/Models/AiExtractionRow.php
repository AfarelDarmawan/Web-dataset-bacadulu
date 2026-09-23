<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiExtractionRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_extraction_job_id',
        'source_document_id',
        'dataset_id',
        'matched_variable_id',
        'applied_observation_id',
        'reviewed_by',
        'row_index',
        'variable_code',
        'variable_name',
        'variable_definition',
        'unit',
        'data_type',
        'geography_code',
        'geography_name',
        'period',
        'value_numeric',
        'value_text',
        'source_locator',
        'source_excerpt',
        'confidence',
        'status',
        'validation_status',
        'validation_issues',
        'admin_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'decimal:6',
            'confidence' => 'decimal:4',
            'validation_issues' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(AiExtractionJob::class, 'ai_extraction_job_id');
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(SourceDocument::class);
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function matchedVariable(): BelongsTo
    {
        return $this->belongsTo(DatasetVariable::class, 'matched_variable_id');
    }

    public function appliedObservation(): BelongsTo
    {
        return $this->belongsTo(DatasetObservation::class, 'applied_observation_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
