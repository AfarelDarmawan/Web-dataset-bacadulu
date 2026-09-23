<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatasetVariable extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'code',
        'name',
        'definition',
        'unit',
        'data_type',
        'category',
        'access_tier',
        'price_per_cell',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_cell' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(DatasetObservation::class);
    }

    public function dataConnectors(): HasMany
    {
        return $this->hasMany(DataConnector::class);
    }
}
