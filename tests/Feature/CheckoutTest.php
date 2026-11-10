<?php

namespace Tests\Feature;

use App\Livewire\CheckoutPage;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
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

    private function addToCart(User $user, string $slug, int $quantity): void
    {
        $result = app(CartService::class)->add($user, $this->product($slug), $quantity);

        $this->assertTrue($result['ok'], $result['message']);
    }

    public function test_guest_cannot_open_checkout_page(): void
    {
        $this->get('/checkout')->assertRedirect('/login');
    }

    public function test_guest_cannot_open_order_detail_page(): void
    {
        $user = $this->customer();
        $this->addToCart($user, 'netflix-premium-1-bulan', 1);

        $order = app(\App\Services\OrderService::class)->createOrder(
            $user,
            $user->name,
            $user->email,
            null
        )['order'];

        $this->assertNotNull($order);

        $this->get('/pesanan/'.$order->id)->assertRedirect('/login');
    }

    public function test_empty_cart_redirects_to_cart_page_with_notice(): void
    {
        $this->actingAs($this->customer())
            ->get('/checkout')
            ->assertRedirect('/keranjang')
            ->assertSessionHas('cart_notice');

        Livewire::actingAs($this->customer())
            ->test(CheckoutPage::class)
            ->assertRedirect(route('cart.index'));
    }

    public function test_checkout_creates_order_snapshots_stock_and_payment_then_clears_cart(): void
    {
        $user = $this->customer();
        $this->addToCart($user, 'netflix-premium-1-bulan', 2);
        $this->addToCart($user, 'canva-pro-1-bulan', 1);

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->assertSee('Checkout')
            ->set('name', 'Pelanggan Mini')
            ->set('email', 'pelanggan@example.test')
            ->set('notes', 'Kirim ke email ini.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertDispatched('cart-updated');

        $order = Order::query()->firstOrFail();

        $this->assertSame(1, Order::count());
        $this->assertMatchesRegularExpression('/^MC-\d{8}-[A-Z0-9]{6}$/', $order->order_number);
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('Pelanggan Mini', $order->buyer_name);
        $this->assertSame('pelanggan@example.test', $order->buyer_email);
        $this->assertSame('Kirim ke email ini.', $order->notes);
        $this->assertNull($order->address_id);
        $this->assertSame(163000, $order->subtotal);
        $this->assertSame(0, $order->shipping_cost);
        $this->assertSame(163000, $order->total);
        $this->assertSame('pending', $order->order_status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('tripay', $order->gateway);
        $this->assertNull($order->gateway_reference);
        $this->assertNull($order->tripay_reference);
        $this->assertNull($order->paid_at);

        $this->assertSame(2, $order->items()->count());

        $netflix = $order->items()->where('product_name', 'Netflix Premium 1 Bulan')->firstOrFail();
        $this->assertSame(69000, $netflix->price_at_checkout);
        $this->assertSame(2, $netflix->quantity);
        $this->assertSame(138000, $netflix->subtotal);

        $canva = $order->items()->where('product_name', 'Canva Pro 1 Bulan')->firstOrFail();
        $this->assertSame(25000, $canva->price_at_checkout);
        $this->assertSame(1, $canva->quantity);
        $this->assertSame(25000, $canva->subtotal);

        $this->assertSame(22, $this->product('netflix-premium-1-bulan')->fresh()->stock);
        $this->assertSame(19, $this->product('canva-pro-1-bulan')->fresh()->stock);

        $this->assertSame(0, CartItem::count());

        $payment = Payment::query()->firstOrFail();
        $this->assertSame($order->id, $payment->order_id);
        $this->assertSame('tripay', $payment->gateway);
        $this->assertSame('pending', $payment->status);
        $this->assertSame($order->order_number, $payment->merchant_ref);
        $this->assertNull($payment->reference);
        $this->assertNull($payment->qr_url);
        $this->assertSame(163000, $payment->amount);
        $this->assertNull($payment->paid_at);

        $this->actingAs($user)
            ->get('/pesanan/'.$order->id)
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Rp163.000')
            ->assertSee('Netflix Premium 1 Bulan')
            ->assertSee('Canva Pro 1 Bulan')
            ->assertSee('Belum dibayar')
            ->assertSee('Menunggu pembayaran')
            ->assertSee('Pelanggan Mini')
            ->assertSee('pelanggan@example.test')
            ->assertSee('Kirim ke email ini.')
            ->assertSee('Lihat pembayaran');
    }

    public function test_checkout_redirects_to_order_detail_after_submit(): void
    {
        $user = $this->customer();
        $this->addToCart($user, 'spotify-premium-1-bulan', 1);

        $test = Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->set('name', 'Pelanggan Mini')
            ->set('email', 'pelanggan@example.test')
            ->call('submit');

        $order = Order::query()->firstOrFail();

        $test->assertRedirect(route('orders.show', ['order' => $order->id, 'created' => 1]));
        $test->assertSessionHas('order_created_banner', $order->id);
    }

    public function test_order_detail_banner_shows_once_after_checkout_only_with_marker(): void
    {
        $user = $this->customer();

        // Penanda query tanpa penanda sesi: tidak ada banner.
        $this->addToCart($user, 'netflix-premium-1-bulan', 1);

        $firstOrder = app(\App\Services\OrderService::class)->createOrder(
            $user,
            $user->name,
            $user->email,
            null
        )['order'];

        $this->assertNotNull($firstOrder);

        $this->actingAs($user)
            ->get('/pesanan/'.$firstOrder->id.'?created=1')
            ->assertOk()
            ->assertDontSee('Pesanan berhasil dibuat.');

        // Checkout lewat Livewire menulis penanda sesi dan redirect
        // dengan ?created=1; pemuatan detail pertama menampilkan banner.
        $this->addToCart($user, 'spotify-premium-1-bulan', 1);

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->set('name', 'Pelanggan Mini')
            ->set('email', 'pelanggan@example.test')
            ->call('submit')
            ->assertHasNoErrors();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->actingAs($user)
            ->get('/pesanan/'.$order->id.'?created=1')
            ->assertOk()
            ->assertSee('Pesanan berhasil dibuat.');

        // Muat ulang dengan penanda yang sama: banner sudah habis.
        $this->actingAs($user)
            ->get('/pesanan/'.$order->id.'?created=1')
            ->assertOk()
            ->assertDontSee('Pesanan berhasil dibuat.');

        // Kunjungan biasa tanpa penanda: tidak ada banner.
        $this->actingAs($user)
            ->get('/pesanan/'.$order->id)
            ->assertOk()
            ->assertDontSee('Pesanan berhasil dibuat.');
    }

    public function test_checkout_validation_rejects_empty_name_and_invalid_email(): void
    {
        $user = $this->customer();
        $this->addToCart($user, 'spotify-premium-1-bulan', 1);

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->set('name', '')
            ->set('email', 'bukan-email')
            ->call('submit')
            ->assertHasErrors(['name', 'email']);

        $this->assertSame(0, Order::count());
        $this->assertSame(1, CartItem::count());
    }

    public function test_checkout_clamps_quantity_when_stock_drops_before_submit(): void
    {
        $user = $this->customer();
        $this->addToCart($user, 'microsoft-365-personal-1-bulan', 8);

        $this->product('microsoft-365-personal-1-bulan')->update(['stock' => 3]);

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->set('name', 'Pelanggan Mini')
            ->set('email', 'pelanggan@example.test')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSessionHas('order_created_banner');

        $order = Order::query()->firstOrFail();
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(3, $order->items()->first()->quantity);
        $this->assertSame(135000, $order->total);
        $this->assertSame(0, $this->product('microsoft-365-personal-1-bulan')->fresh()->stock);
        $this->assertSame(0, CartItem::count());
    }

    public function test_checkout_removes_out_of_stock_item_and_fails_when_cart_becomes_empty(): void
    {
        $user = $this->customer();
        $this->addToCart($user, 'spotify-premium-1-bulan', 1);

        $this->product('spotify-premium-1-bulan')->update(['stock' => 0]);

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->set('name', 'Pelanggan Mini')
            ->set('email', 'pelanggan@example.test')
            ->call('submit')
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('cart_notice');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, CartItem::count());
    }

    public function test_checkout_removes_inactive_product_and_fails_when_cart_becomes_empty(): void
    {
        $user = $this->customer();
        $this->addToCart($user, 'spotify-premium-1-bulan', 1);

        $this->product('spotify-premium-1-bulan')->update(['is_active' => false]);

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->set('name', 'Pelanggan Mini')
            ->set('email', 'pelanggan@example.test')
            ->call('submit')
            ->assertRedirect(route('cart.index'));

        $this->assertSame(0, Order::count());
        $this->assertSame(0, CartItem::count());
    }

    public function test_other_user_cannot_open_order_detail_page(): void
    {
        $user = $this->customer();
        $this->addToCart($user, 'spotify-premium-1-bulan', 1);

        $order = app(\App\Services\OrderService::class)->createOrder(
            $user,
            $user->name,
            $user->email,
            null
        )['order'];

        $this->assertNotNull($order);

        $other = User::factory()->create(['role' => 'customer']);

        $this->actingAs($other)
            ->get('/pesanan/'.$order->id)
            ->assertNotFound();
    }

    public function test_checkout_uses_latest_price_when_price_changes_after_adding_to_cart(): void
    {
        $user = $this->customer();
        $this->addToCart($user, 'canva-pro-1-bulan', 1);

        $this->product('canva-pro-1-bulan')->update(['price' => 30000]);

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->set('name', 'Pelanggan Mini')
            ->set('email', 'pelanggan@example.test')
            ->call('submit')
            ->assertHasNoErrors();

        $order = Order::query()->firstOrFail();
        $this->assertSame(30000, $order->items()->first()->price_at_checkout);
        $this->assertSame(30000, $order->total);
    }

    public function test_checkout_page_shows_summary_and_digital_delivery_note(): void
    {
        $user = $this->customer();
        $this->addToCart($user, 'netflix-premium-1-bulan', 1);

        $this->actingAs($user)
            ->get('/checkout')
            ->assertOk()
            ->assertSee('Checkout')
            ->assertSee('Netflix Premium 1 Bulan')
            ->assertSee('Rp69.000')
            ->assertSee('Ongkos kirim')
            ->assertSee('Rp0')
            ->assertSee('Produk digital dikirim ke email')
            ->assertSee('Buat pesanan');
    }

    public function test_cart_page_links_to_checkout(): void
    {
        $user = $this->customer();
        $this->addToCart($user, 'netflix-premium-1-bulan', 1);

        $this->actingAs($user)
            ->get('/keranjang')
            ->assertOk()
            ->assertSee('Lanjut ke checkout')
            ->assertSee('bayar dengan QRIS dari halaman detail pesanan');
    }
}
