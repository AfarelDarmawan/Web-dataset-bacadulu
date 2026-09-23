<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class DataConnector extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'dataset_variable_id',
        'created_by',
        'name',
        'type',
        'status',
        'schedule',
        'config',
        'last_started_at',
        'last_succeeded_at',
        'next_sync_at',
        'consecutive_failures',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'last_started_at' => 'datetime',
            'last_succeeded_at' => 'datetime',
            'next_sync_at' => 'datetime',
            'consecutive_failures' => 'integer',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(DataSyncRun::class);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where('schedule', '!=', 'manual')
            ->whereNotNull('next_sync_at')
            ->where('next_sync_at', '<=', now());
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function nextScheduledAt(?Carbon $from = null, ?string $schedule = null): ?Carbon
    {
        $from ??= now();
        $schedule ??= $this->schedule;

        return match ($schedule) {
            'daily' => $from->copy()->addDay(),
            'weekly' => $from->copy()->addWeek(),
            'monthly' => $from->copy()->addMonthNoOverflow(),
            default => null,
        };
    }
}
