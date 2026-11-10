<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'gateway',
        'reference',
        'merchant_ref',
        'qr_url',
        'amount',
        'status',
        'paid_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu pembayaran',
            'paid' => 'Sudah dibayar',
            'expired' => 'Kedaluwarsa',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
            'refunded' => 'Dikembalikan',
            default => ucwords(str_replace('_', ' ', $this->status)),
        };
    }
}
