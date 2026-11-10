<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'buyer_name',
        'buyer_email',
        'notes',
        'address_id',
        'subtotal',
        'shipping_cost',
        'total',
        'order_status',
        'payment_status',
        'gateway',
        'gateway_reference',
        'tripay_reference',
        'qr_expires_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'shipping_cost' => 'integer',
            'total' => 'integer',
            'qr_expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Pembayaran yang dipakai seluruh alur (TripayService dan
     * PaymentPage memakai baris pertama berdasarkan id; satu
     * order hanya punya satu baris Payment).
     */
    public function currentPayment(): ?Payment
    {
        return $this->payments()->orderBy('id')->first();
    }

    /**
     * Pembatalan manual hanya diizinkan bila order masih
     * menunggu, pembayarannya masih menunggu, dan tidak ada
     * kode QR yang masih aktif. Kode QR dianggap aktif bila
     * sudah ada referensi Tripay dan waktu kedaluwarsanya
     * belum lewat.
     */
    public function isCancellable(): bool
    {
        if ($this->order_status !== 'pending') {
            return false;
        }

        $payment = $this->currentPayment();

        if ($payment === null) {
            return true;
        }

        if ($payment->status !== 'pending') {
            return false;
        }

        if ($payment->reference === null) {
            return true;
        }

        return $this->qr_expires_at !== null && now()->greaterThanOrEqualTo($this->qr_expires_at);
    }

    /**
     * Alasan pembatalan tidak diizinkan untuk order yang masih
     * menunggu tetapi kode QR-nya masih aktif. Mengembalikan
     * null bila tidak ada penjelasan khusus yang perlu
     * ditampilkan.
     */
    public function cancelBlockReason(): ?string
    {
        if ($this->order_status !== 'pending' || $this->isCancellable()) {
            return null;
        }

        $payment = $this->currentPayment();

        if ($payment !== null && $payment->status === 'pending' && $payment->reference !== null) {
            return 'Pesanan ini belum bisa dibatalkan karena kode QR pembayarannya masih aktif. Pembatalan baru bisa dilakukan setelah kode QR kedaluwarsa.';
        }

        return null;
    }

    public function formattedTotal(): string
    {
        return 'Rp' . number_format($this->total, 0, ',', '.');
    }

    public function formattedSubtotal(): string
    {
        return 'Rp' . number_format($this->subtotal, 0, ',', '.');
    }

    public function orderStatusLabel(): string
    {
        return match ($this->order_status) {
            'pending' => 'Menunggu pembayaran',
            'paid' => 'Dibayar',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => ucwords(str_replace('_', ' ', $this->order_status)),
        };
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'unpaid' => 'Belum dibayar',
            'pending' => 'Menunggu pembayaran',
            'paid' => 'Sudah dibayar',
            'failed' => 'Gagal',
            default => ucwords(str_replace('_', ' ', $this->payment_status)),
        };
    }
}
