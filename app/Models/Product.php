<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'image_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    /**
     * Nama dasar tanpa akhiran durasi ("1 Bulan", "3 Bulan",
     * "1 Tahun"). Bila nama tidak berakhiran durasi, nama
     * dikembalikan apa adanya.
     */
    public function baseName(): string
    {
        return trim((string) preg_replace('/\s+\d+\s+(?:Bulan|Tahun)\s*$/i', '', $this->name));
    }

    /**
     * Benar bila nama produk berakhiran durasi, misalnya
     * "Netflix Premium 1 Bulan" atau "Canva Pro 1 Tahun".
     */
    public function hasDurationSuffix(): bool
    {
        return (bool) preg_match('/\s+\d+\s+(?:Bulan|Tahun)\s*$/i', $this->name);
    }

    public function formattedPrice(): string
    {
        return 'Rp' . number_format($this->price, 0, ',', '.');
    }

    /**
     * URL gambar siap pakai di <img>. Path relatif (mis.
     * "images/products/nama.jpg") diubah menjadi URL aset
     * lokal; URL http(s) diteruskan apa adanya supaya
     * gambar jarak jauh yang sudah tersimpan tetap jalan.
     * Mengembalikan null bila tidak ada gambar.
     */
    public function displayImageUrl(): ?string
    {
        $url = $this->image_url;

        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (str_starts_with($url, 'http://')
            || str_starts_with($url, 'https://')
            || str_starts_with($url, '//')
            || str_starts_with($url, 'data:')
        ) {
            return $url;
        }

        return asset(ltrim($url, '/'));
    }
}
