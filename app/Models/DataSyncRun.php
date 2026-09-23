<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataSyncRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_connector_id',
        'triggered_by',
        'trigger',
        'status',
        'rows_count',
        'valid_rows_count',
        'warning_rows_count',
        'invalid_rows_count',
        'created_rows_count',
        'updated_rows_count',
        'unchanged_rows_count',
        'response_checksum',
        'metadata',
        'error_code',
        'error_message',
        'started_at',
        'fetched_at',
        'completed_at',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'started_at' => 'datetime',
            'fetched_at' => 'datetime',
            'completed_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(DataConnector::class, 'data_connector_id');
    }

    public function triggerer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(DataSyncRow::class);
    }

    public function isReviewable(): bool
    {
        return $this->status === 'review';
    }
}
