<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProductDetail extends Component
{
    public Product $product;

    public int $quantity = 1;

    public ?string $cartMessage = null;

    public ?string $cartError = null;

    public function mount(Product $product): void
    {
        $this->product = $product->load('category');
    }

    public function decreaseQuantity(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function increaseQuantity(): void
    {
        $this->quantity = min($this->product->stock, $this->quantity + 1);
    }

    public function updateQuantityInput(mixed $value): void
    {
        if (! is_numeric($value)) {
            return;
        }

        $this->quantity = max(1, min((int) $value, $this->product->stock));
    }

    public function addToCart(CartService $cartService): void
    {
        $user = auth()->user();

        if ($user === null) {
            $this->redirect(route('login'));

            return;
        }

        $this->cartMessage = null;
        $this->cartError = null;

        $result = $cartService->add($user, $this->product, $this->quantity);

        if (! $result['ok']) {
            $this->cartError = $result['message'];

            return;
        }

        $this->cartMessage = $result['message'];
        $this->quantity = 1;
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        $related = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->where('category_id', $this->product->category_id)
            ->where('id', '!=', $this->product->id)
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        // Durasi lain: produk se-nama dasar dalam kategori yang
        // sama, hanya bila produk ini sendiri berakhiran durasi.
        // Disaring di PHP karena akhiran durasi adalah pola nama,
        // bukan kolom; katalog kecil sehingga ini cukup.
        $durationBaseName = null;
        $durationVariants = collect();

        if ($this->product->hasDurationSuffix()) {
            $durationBaseName = $this->product->baseName();

            $durationVariants = Product::query()
                ->with('category')
                ->where('is_active', true)
                ->where('category_id', $this->product->category_id)
                ->where('id', '!=', $this->product->id)
                ->get()
                ->filter(fn (Product $candidate) => $candidate->hasDurationSuffix()
                    && $candidate->baseName() === $durationBaseName)
                ->sortBy('price')
                ->values();
        }

        return view('livewire.product-detail', [
            'related' => $related,
            'durationBaseName' => $durationBaseName,
            'durationVariants' => $durationVariants,
        ]);
    }
}
