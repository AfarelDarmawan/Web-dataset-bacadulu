<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSyncRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_sync_run_id',
        'dataset_variable_id',
        'existing_observation_id',
        'row_index',
        'external_key',
        'geography_code',
        'geography_name',
        'period',
        'value_numeric',
        'value_text',
        'source_reference',
        'proposed_action',
        'status',
        'validation_status',
        'validation_issues',
        'source_payload',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'decimal:6',
            'validation_issues' => 'array',
            'source_payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(DataSyncRun::class, 'data_sync_run_id');
    }

    public function variable(): BelongsTo
    {
        return $this->belongsTo(DatasetVariable::class, 'dataset_variable_id');
    }

    public function existingObservation(): BelongsTo
    {
        return $this->belongsTo(DatasetObservation::class, 'existing_observation_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
