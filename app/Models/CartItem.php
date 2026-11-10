<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_id', 'quantity', 'price'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'integer',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subtotal(): int
    {
        return $this->quantity * $this->price;
    }

    public function formattedUnitPrice(): string
    {
        return 'Rp'.number_format($this->price, 0, ',', '.');
    }

    public function formattedSubtotal(): string
    {
        return 'Rp'.number_format($this->subtotal(), 0, ',', '.');
    }
}
