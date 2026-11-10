<?php

namespace App\Livewire;

use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    public int $count = 0;

    public string $variant = 'sidebar';

    public function mount(string $variant = 'sidebar'): void
    {
        $this->variant = $variant;
        $this->refreshCount();
    }

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        $user = auth()->user();

        $this->count = $user === null
            ? 0
            : app(CartService::class)->totalQuantity($user);
    }

    public function render(): View
    {
        return view('livewire.cart-counter');
    }
}
