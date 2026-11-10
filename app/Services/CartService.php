<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function cartFor(User $user): ?Cart
    {
        return Cart::query()->where('user_id', $user->id)->first();
    }

    public function getOrCreateCart(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function itemsFor(User $user): Collection
    {
        $cart = $this->cartFor($user);

        if ($cart === null) {
            return collect();
        }

        return CartItem::query()
            ->with(['product.category'])
            ->where('cart_id', $cart->id)
            ->orderBy('created_at')
            ->get();
    }

    public function totalQuantity(User $user): int
    {
        $cart = $this->cartFor($user);

        return $cart === null ? 0 : (int) $cart->items()->sum('quantity');
    }

    public function subtotal(User $user): int
    {
        return $this->itemsFor($user)->sum(
            fn (CartItem $item) => $item->quantity * $item->price
        );
    }

    /**
     * Add a product to the user's cart. Stock is re-checked server-side
     * inside a transaction and the quantity is capped at current stock.
     *
     * @return array{ok: bool, capped: bool, quantity: int, message: string, item: ?CartItem}
     */
    public function add(User $user, Product $product, int $quantity): array
    {
        return DB::transaction(function () use ($user, $product, $quantity) {
            $fresh = Product::query()->find($product->getKey());

            if ($fresh === null || ! $fresh->is_active || $fresh->stock <= 0) {
                return [
                    'ok' => false,
                    'capped' => false,
                    'quantity' => 0,
                    'message' => 'Produk tidak tersedia.',
                    'item' => null,
                ];
            }

            $requested = max(1, $quantity);
            $cart = $this->getOrCreateCart($user);

            $item = CartItem::query()->firstOrNew([
                'cart_id' => $cart->id,
                'product_id' => $fresh->id,
            ]);

            $existing = $item->exists ? $item->quantity : 0;
            $newQuantity = $existing + $requested;
            $capped = $newQuantity > $fresh->stock;

            if ($capped) {
                $newQuantity = $fresh->stock;
            }

            if (! $item->exists) {
                $item->price = $fresh->price;
            }

            $item->quantity = $newQuantity;
            $item->save();

            return [
                'ok' => true,
                'capped' => $capped,
                'quantity' => $newQuantity,
                'message' => $capped
                    ? "Jumlah disesuaikan menjadi {$newQuantity} sesuai stok yang tersedia."
                    : 'Produk ditambahkan ke keranjang.',
                'item' => $item,
            ];
        });
    }

    /**
     * Set a cart item quantity, clamped to 1..stock. Items whose product
     * is inactive or out of stock are removed instead.
     *
     * @return array{ok: bool, removed: bool, capped: bool, quantity: int, message: string}
     */
    public function updateQuantity(User $user, CartItem $item, int $quantity): array
    {
        return DB::transaction(function () use ($user, $item, $quantity) {
            $item->loadMissing('cart');

            if ($item->cart === null || $item->cart->user_id !== $user->id) {
                return [
                    'ok' => false,
                    'removed' => false,
                    'capped' => false,
                    'quantity' => 0,
                    'message' => 'Item keranjang tidak ditemukan.',
                ];
            }

            $fresh = Product::query()->find($item->product_id);

            if ($fresh === null || ! $fresh->is_active || $fresh->stock <= 0) {
                $name = $item->product?->name ?? 'Produk';
                $item->delete();

                return [
                    'ok' => true,
                    'removed' => true,
                    'capped' => false,
                    'quantity' => 0,
                    'message' => "{$name} dihapus dari keranjang karena sudah tidak tersedia.",
                ];
            }

            $newQuantity = max(1, min($quantity, $fresh->stock));
            $capped = $quantity > $fresh->stock;
            $item->quantity = $newQuantity;
            $item->save();

            return [
                'ok' => true,
                'removed' => false,
                'capped' => $capped,
                'quantity' => $newQuantity,
                'message' => $capped
                    ? "Jumlah disesuaikan menjadi {$newQuantity} sesuai stok yang tersedia."
                    : 'Jumlah diperbarui.',
            ];
        });
    }

    public function remove(User $user, CartItem $item): bool
    {
        $item->loadMissing('cart');

        if ($item->cart === null || $item->cart->user_id !== $user->id) {
            return false;
        }

        $item->delete();

        return true;
    }

    /**
     * Clamp or remove items that are no longer valid (inactive product,
     * stock dropped below the cart quantity, or stock is now zero).
     * Returns one Indonesian notice per change, in plain language.
     *
     * @return list<string>
     */
    public function validateCart(User $user): array
    {
        $notices = [];

        foreach ($this->itemsFor($user) as $item) {
            $product = $item->product;

            if ($product === null || ! $product->is_active || $product->stock <= 0) {
                $notices[] = ($product?->name ?? 'Produk').' dihapus dari keranjang karena sudah tidak tersedia.';
                $item->delete();
                continue;
            }

            if ($item->quantity > $product->stock) {
                $item->quantity = $product->stock;
                $item->save();
                $notices[] = "Jumlah {$product->name} disesuaikan menjadi {$product->stock} sesuai stok yang tersedia.";
            }
        }

        return $notices;
    }
}
