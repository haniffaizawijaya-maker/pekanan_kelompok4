<?php

namespace App\Livewire;

use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CartPage extends Component
{
    /** @var list<string> */
    public array $notices = [];

    public ?string $message = null;

    public function mount(CartService $cartService): void
    {
        $this->notices = $cartService->validateCart(auth()->user());

        if ($this->notices !== []) {
            $this->dispatch('cart-updated');
        }
    }

    public function increment(int $itemId, CartService $cartService): void
    {
        $item = $this->ownItem($itemId);

        if ($item === null) {
            return;
        }

        $result = $cartService->updateQuantity(auth()->user(), $item, $item->quantity + 1);

        $this->afterMutation($result);
    }

    public function decrement(int $itemId, CartService $cartService): void
    {
        $item = $this->ownItem($itemId);

        if ($item === null) {
            return;
        }

        $result = $cartService->updateQuantity(auth()->user(), $item, $item->quantity - 1);

        $this->afterMutation($result);
    }

    public function setQuantity(int $itemId, mixed $value, CartService $cartService): void
    {
        $item = $this->ownItem($itemId);

        if ($item === null) {
            return;
        }

        if (! is_numeric($value)) {
            $this->message = 'Jumlah harus berupa angka.';

            return;
        }

        $result = $cartService->updateQuantity(auth()->user(), $item, (int) $value);

        $this->afterMutation($result);
    }

    public function remove(int $itemId, CartService $cartService): void
    {
        $item = $this->ownItem($itemId);

        if ($item === null) {
            return;
        }

        if ($cartService->remove(auth()->user(), $item)) {
            $this->message = 'Produk dihapus dari keranjang.';
            $this->dispatch('cart-updated');
        }
    }

    private function ownItem(int $itemId): ?CartItem
    {
        $cart = app(CartService::class)->cartFor(auth()->user());

        if ($cart === null) {
            return null;
        }

        return CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('id', $itemId)
            ->first();
    }

    /**
     * @param  array{ok: bool, removed: bool, capped: bool, quantity: int, message: string}  $result
     */
    private function afterMutation(array $result): void
    {
        if (! $result['ok']) {
            $this->message = $result['message'];

            return;
        }

        if ($result['capped'] || $result['removed']) {
            $this->message = $result['message'];
        } else {
            $this->message = 'Jumlah diperbarui.';
        }

        $this->dispatch('cart-updated');
    }

    public function render(CartService $cartService): View
    {
        $user = auth()->user();
        $items = $cartService->itemsFor($user);

        return view('livewire.cart-page', [
            'items' => $items,
            'subtotal' => $cartService->subtotal($user),
            'totalQuantity' => $cartService->totalQuantity($user),
        ]);
    }
}
