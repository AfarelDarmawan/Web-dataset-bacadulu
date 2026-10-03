<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataAccessRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'user_id',
        'dataset_id',
        'variable_ids',
        'geographies',
        'periods',
        'research_purpose',
        'status',
        'estimated_cells',
        'estimated_price',
        'payment_status',
        'payment_reference',
        'paid_at',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
        'expires_at',
        'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'variable_ids' => 'array',
            'geographies' => 'array',
            'periods' => 'array',
            'estimated_price' => 'decimal:2',
            'paid_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
            'downloaded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function requiresPayment(): bool
    {
        return (float) $this->estimated_price > 0;
    }

    public function canDownload(): bool
    {
        return $this->status === 'approved'
            && (! $this->requiresPayment() || $this->payment_status === 'paid')
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'awaiting_invoice' => 'Menunggu rincian pembayaran',
            'awaiting_payment' => 'Menunggu pembayaran',
            'payment_review' => 'Bukti bayar sedang diperiksa',
            'paid' => 'Pembayaran terverifikasi',
            'failed' => 'Pembayaran bermasalah',
            'refunded' => 'Dana dikembalikan',
            default => 'Tidak memerlukan pembayaran',
        };
    }
}