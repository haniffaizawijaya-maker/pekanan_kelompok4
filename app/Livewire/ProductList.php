<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $category = 'semua';

    #[Url(history: true)]
    public string $sort = 'terbaru';

    public ?string $cartMessage = null;

    public ?string $cartError = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function setCategory(string $slug): void
    {
        $this->category = $slug;
        $this->resetPage();
    }

    public function addToCart(int $productId, CartService $cartService): void
    {
        $user = auth()->user();

        if ($user === null) {
            $this->redirect(route('login'));

            return;
        }

        $this->cartMessage = null;
        $this->cartError = null;

        $product = Product::query()->find($productId);

        if ($product === null || ! $product->is_active) {
            $this->cartError = 'Produk tidak tersedia.';

            return;
        }

        $result = $cartService->add($user, $product, 1);

        if (! $result['ok']) {
            $this->cartError = $result['message'];

            return;
        }

        $this->cartMessage = $result['message'];
        $this->dispatch('cart-updated');
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'category',
            'sort',
        ]);
        $this->search = '';
        $this->category = 'semua';
        $this->sort = 'terbaru';
        $this->resetPage();
    }

    public function render(): View
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($this->category !== 'semua', function ($query) {
                $query->whereHas('category', function ($inner) {
                    $inner->where('slug', $this->category);
                });
            })
            ->when($this->sort === 'harga-terendah', fn ($query) => $query->orderBy('price', 'asc'))
            ->when($this->sort === 'harga-tertinggi', fn ($query) => $query->orderBy('price', 'desc'))
            ->when($this->sort === 'nama', fn ($query) => $query->orderBy('name', 'asc'))
            ->when($this->sort === 'terbaru', fn ($query) => $query->orderByDesc('created_at'))
            ->paginate(9);

        return view('livewire.product-list', [
            'categories' => $categories,
            'products' => $products,
        ]);
    }
}
