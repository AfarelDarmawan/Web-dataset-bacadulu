<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'uploaded_by',
        'original_name',
        'storage_path',
        'disk',
        'mime_type',
        'extension',
        'size_bytes',
        'sha256',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size_bytes' => 'integer',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function extractionJobs(): HasMany
    {
        return $this->hasMany(AiExtractionJob::class);
    }

    public function extractionRows(): HasMany
    {
        return $this->hasMany(AiExtractionRow::class);
    }
}
