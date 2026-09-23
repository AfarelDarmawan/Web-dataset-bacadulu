<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dataset extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_provider_id',
        'owner_id',
        'title',
        'slug',
        'code',
        'summary',
        'description',
        'methodology',
        'data_scope',
        'category',
        'frequency',
        'geographic_level',
        'period_start',
        'period_end',
        'license',
        'source_url',
        'access_type',
        'status',
        'published_at',
        'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'last_updated_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(DataProvider::class, 'data_provider_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function variables(): HasMany
    {
        return $this->hasMany(DatasetVariable::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(DatasetObservation::class);
    }

    public function accessRequests(): HasMany
    {
        return $this->hasMany(DataAccessRequest::class);
    }

    public function sourceDocuments(): HasMany
    {
        return $this->hasMany(SourceDocument::class);
    }

    public function extractionJobs(): HasMany
    {
        return $this->hasMany(AiExtractionJob::class);
    }

    public function dataConnectors(): HasMany
    {
        return $this->hasMany(DataConnector::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereHas('provider', fn (Builder $provider) => $provider->where('status', 'active'));
    }

    public function isOpen(): bool
    {
        return $this->access_type === 'open';
    }

    public function isCorporate(): bool
    {
        return $this->data_scope === 'corporate';
    }

    public function scopeLabel(): string
    {
        return $this->isCorporate()
            ? 'Perusahaan & ESG'
            : 'Statistik wilayah & pemerintah';
    }

    public function entityLabel(): string
    {
        return $this->isCorporate() ? 'Perusahaan / emiten' : 'Wilayah / entitas';
    }

    public function entityLabelLower(): string
    {
        return $this->isCorporate() ? 'perusahaan atau emiten' : 'wilayah atau entitas';
    }

    public function isPubliclyAvailable(): bool
    {
        return $this->status === 'published' && $this->provider?->status === 'active';
    }
}
