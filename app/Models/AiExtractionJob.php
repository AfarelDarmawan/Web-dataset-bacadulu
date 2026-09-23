<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiExtractionJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_document_id',
        'dataset_id',
        'requested_by',
        'reviewed_by',
        'status',
        'extractor',
        'model',
        'prompt_version',
        'overall_confidence',
        'response_id',
        'usage',
        'summary',
        'error_code',
        'error_message',
        'started_at',
        'completed_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'overall_confidence' => 'decimal:4',
            'usage' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(SourceDocument::class);
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(AiExtractionRow::class);
    }

    public function isReviewable(): bool
    {
        return $this->status === 'review';
    }

    public function isStale(): bool
    {
        if (! in_array($this->status, ['queued', 'processing'], true)) {
            return false;
        }

        $started = $this->started_at ?? $this->created_at;

        return $started !== null
            && $started->lte(now()->subSeconds((int) config('bacadulu.ai.stale_after_seconds', 300)));
    }
}
