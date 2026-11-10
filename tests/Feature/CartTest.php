<?php

namespace Tests\Feature;

use App\Livewire\CartCounter;
use App\Livewire\CartPage;
use App\Livewire\ProductDetail;
use App\Livewire\ProductList;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([UserSeeder::class, CategorySeeder::class, ProductSeeder::class]);
    }

    private function customer(): User
    {
        return User::where('email', 'customer@minicom.test')->firstOrFail();
    }

    private function product(string $slug): Product
    {
        return Product::where('slug', $slug)->firstOrFail();
    }

    public function test_guest_cannot_open_cart_page(): void
    {
        $this->get('/keranjang')->assertRedirect('/login');
    }

    public function test_guest_add_from_detail_redirects_to_login_without_creating_cart(): void
    {
        $product = $this->product('netflix-premium-1-bulan');

        Livewire::test(ProductDetail::class, ['product' => $product])
            ->call('addToCart')
            ->assertRedirect('/login');

        $this->assertSame(0, Cart::count());
        $this->assertSame(0, CartItem::count());
    }

    public function test_guest_add_from_catalog_redirects_to_login(): void
    {
        $product = $this->product('netflix-premium-1-bulan');

        Livewire::test(ProductList::class)
            ->call('addToCart', $product->id)
            ->assertRedirect('/login');

        $this->assertSame(0, Cart::count());
    }

    public function test_logged_in_user_can_add_from_detail_and_second_add_increments(): void
    {
        $user = $this->customer();
        $product = $this->product('netflix-premium-1-bulan');

        Livewire::actingAs($user)
            ->test(ProductDetail::class, ['product' => $product])
            ->set('quantity', 2)
            ->call('addToCart')
            ->assertSee('Produk ditambahkan ke keranjang.');

        $this->assertSame(2, CartItem::first()->quantity);

        Livewire::actingAs($user)
            ->test(ProductDetail::class, ['product' => $product])
            ->set('quantity', 3)
            ->call('addToCart');

        $this->assertSame(5, CartItem::first()->quantity);
        $this->assertSame(1, CartItem::count());
    }

    public function test_add_from_catalog_adds_one_item(): void
    {
        $user = $this->customer();
        $product = $this->product('microsoft-365-personal-1-bulan');

        Livewire::actingAs($user)
            ->test(ProductList::class)
            ->call('addToCart', $product->id)
            ->assertSee('Produk ditambahkan ke keranjang.');

        $item = CartItem::first();
        $this->assertSame(1, $item->quantity);
        $this->assertSame(45000, $item->price);
    }

    public function test_quantity_is_capped_at_stock(): void
    {
        $user = $this->customer();
        $product = $this->product('microsoft-365-personal-1-bulan'); // stock 8

        Livewire::actingAs($user)
            ->test(ProductDetail::class, ['product' => $product])
            ->set('quantity', 20)
            ->call('addToCart')
            ->assertSee('Jumlah disesuaikan menjadi 8 sesuai stok yang tersedia.');

        $this->assertSame(8, CartItem::first()->quantity);
    }

    public function test_stock_zero_product_cannot_be_added(): void
    {
        $user = $this->customer();
        $product = $this->product('zoom-pro-1-bulan'); // stock 0

        Livewire::actingAs($user)
            ->test(ProductDetail::class, ['product' => $product])
            ->call('addToCart')
            ->assertSee('Produk tidak tersedia.');

        $this->assertSame(0, CartItem::count());
    }

    public function test_quantity_update_remove_and_subtotal_on_cart_page(): void
    {
        $user = $this->customer();
        $service = app(CartService::class);
        $service->add($user, $this->product('netflix-premium-1-bulan'), 1);
        $service->add($user, $this->product('microsoft-365-personal-1-bulan'), 2);

        $item = CartItem::where('product_id', $this->product('netflix-premium-1-bulan')->id)->firstOrFail();

        Livewire::actingAs($user)
            ->test(CartPage::class)
            ->assertSee('Netflix Premium 1 Bulan')
            ->assertSee('Rp159.000')
            ->call('setQuantity', $item->id, 3)
            ->assertSee('Rp297.000')
            ->call('remove', $item->id)
            ->assertDontSee('Netflix Premium 1 Bulan')
            ->assertSee('Microsoft 365 Personal 1 Bulan');
    }

    public function test_cart_is_per_user(): void
    {
        $first = $this->customer();
        $second = User::factory()->create(['role' => 'customer']);
        $service = app(CartService::class);
        $service->add($first, $this->product('netflix-premium-1-bulan'), 1);

        Livewire::actingAs($second)
            ->test(CartPage::class)
            ->assertSee('Keranjang masih kosong')
            ->assertDontSee('Netflix Premium 1 Bulan');

        $this->assertSame(0, $service->totalQuantity($second));
        $this->assertSame(1, $service->totalQuantity($first));
    }

    public function test_empty_cart_state_renders(): void
    {
        Livewire::actingAs($this->customer())
            ->test(CartPage::class)
            ->assertSee('Keranjang masih kosong')
            ->assertSee('Lihat katalog');
    }

    public function test_cart_page_clamps_quantity_when_stock_drops(): void
    {
        $user = $this->customer();
        $product = $this->product('microsoft-365-personal-1-bulan'); // stock 8
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 8,
            'price' => $product->price,
        ]);

        $product->update(['stock' => 2]);

        Livewire::actingAs($user)
            ->test(CartPage::class)
            ->assertSee('Jumlah Microsoft 365 Personal 1 Bulan disesuaikan menjadi 2 sesuai stok yang tersedia.');

        $this->assertSame(2, CartItem::first()->quantity);
    }

    public function test_cart_page_removes_item_when_product_becomes_inactive(): void
    {
        $user = $this->customer();
        $product = $this->product('netflix-premium-1-bulan');
        app(CartService::class)->add($user, $product, 1);
        $product->update(['is_active' => false]);

        Livewire::actingAs($user)
            ->test(CartPage::class)
            ->assertSee('dihapus dari keranjang karena sudah tidak tersedia.')
            ->assertSee('Keranjang masih kosong');

        $this->assertSame(0, CartItem::count());
    }

    public function test_header_counter_shows_total_quantity(): void
    {
        $user = $this->customer();
        $service = app(CartService::class);
        $service->add($user, $this->product('netflix-premium-1-bulan'), 2);
        $service->add($user, $this->product('microsoft-365-personal-1-bulan'), 1);

        Livewire::actingAs($user)
            ->test(CartCounter::class)
            ->assertSee('3');
    }

    public function test_header_counter_on_cart_page_matches_total_quantity(): void
    {
        $user = $this->customer();
        $service = app(CartService::class);
        $service->add($user, $this->product('netflix-premium-1-bulan'), 2);
        $service->add($user, $this->product('microsoft-365-personal-1-bulan'), 1);

        $this->actingAs($user)
            ->get('/keranjang')
            ->assertStatus(200)
            ->assertSee('Keranjang, 3 item', false)
            ->assertSee('3 item di keranjang.');
    }

    public function test_both_counter_instances_render_same_count_without_duplication(): void
    {
        $user = $this->customer();
        $service = app(CartService::class);
        $service->add($user, $this->product('netflix-premium-1-bulan'), 2);
        $service->add($user, $this->product('microsoft-365-personal-1-bulan'), 1);

        $response = $this->actingAs($user)->get('/')->assertStatus(200);
        $html = $response->getContent();

        // Tepat dua instans counter (sidebar + menu mobile), angka sama.
        $this->assertSame(2, substr_count($html, 'aria-label="Keranjang, 3 item"'));

        // Setiap komponen Livewire punya wire:id unik; duplikasi id adalah
        // gejala yang menyertai cacat morph counter di browser.
        preg_match_all('/wire:id="([^"]+)"/', $html, $matches);
        $ids = $matches[1];
        $this->assertNotEmpty($ids);
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    public function test_cart_page_links_to_checkout_and_notes_qris_payment(): void
    {
        $user = $this->customer();
        app(CartService::class)->add($user, $this->product('netflix-premium-1-bulan'), 1);

        $this->actingAs($user)
            ->get('/keranjang')
            ->assertStatus(200)
            ->assertSee('Lanjut ke checkout')
            ->assertSee('bayar dengan QRIS dari halaman detail pesanan')
            ->assertSee('Lanjut belanja');
    }
}
