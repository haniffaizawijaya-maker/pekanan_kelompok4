<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tes Fase 5: riwayat pesanan, pembatalan bersama, dan panel
 * admin. Tanpa jaringan Tripay; callback diuji dengan
 * signature HMAC dari kunci FAKE yang diset di config tes.
 */
class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([UserSeeder::class, CategorySeeder::class, ProductSeeder::class]);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@minicom.test')->firstOrFail();
    }

    private function customer(): User
    {
        return User::where('email', 'customer@minicom.test')->firstOrFail();
    }

    private function product(string $slug = 'netflix-premium-1-bulan'): Product
    {
        return Product::where('slug', $slug)->firstOrFail();
    }

    private function makeOrder(User $user, string $slug = 'netflix-premium-1-bulan', int $quantity = 1): Order
    {
        $result = app(CartService::class)->add($user, $this->product($slug), $quantity);
        $this->assertTrue($result['ok'], $result['message']);

        $created = app(OrderService::class)->createOrder($user, $user->name, $user->email, null);

        $this->assertTrue($created['ok'], $created['message']);
        $this->assertNotNull($created['order']);

        return $created['order'];
    }

    private function paymentOf(Order $order): Payment
    {
        return Payment::query()->where('order_id', $order->id)->orderBy('id')->firstOrFail();
    }

    private function setPaid(Order $order): void
    {
        $order->update(['order_status' => 'paid', 'payment_status' => 'paid', 'paid_at' => now()]);
        $this->paymentOf($order)->update(['status' => 'paid', 'paid_at' => now()]);
    }

    public function test_guest_redirected_to_login_for_orders_and_admin(): void
    {
        $this->get('/pesanan')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/pesanan')->assertRedirect('/login');
        $this->get('/admin/produk')->assertRedirect('/login');
    }

    public function test_customer_gets_403_for_all_admin_routes(): void
    {
        $this->actingAs($this->customer());

        $this->get('/admin')->assertForbidden();
        $this->get('/admin/pesanan')->assertForbidden();
        $this->get('/admin/produk')->assertForbidden();
        $this->get('/admin/produk/buat')->assertForbidden();
    }

    public function test_admin_can_open_dashboard_and_customer_can_open_own_list(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertOk();
        $this->actingAs($this->customer())->get('/pesanan')->assertOk();
    }

    public function test_customer_list_shows_only_own_orders_and_filters_work(): void
    {
        $own = $this->makeOrder($this->customer());

        $other = User::factory()->create(['role' => 'customer']);
        $foreign = $this->makeOrder($other);

        $this->actingAs($this->customer());

        $this->get('/pesanan')
            ->assertOk()
            ->assertSee($own->order_number)
            ->assertDontSee($foreign->order_number);

        $this->get('/pesanan?'.http_build_query(['q' => $own->order_number]))
            ->assertOk()
            ->assertSee($own->order_number);

        $this->get('/pesanan?'.http_build_query(['order_status' => 'paid']))
            ->assertOk()
            ->assertDontSee($own->order_number);
    }

    public function test_customer_cancel_restores_stock_exactly_once(): void
    {
        $product = $this->product();
        $before = $product->stock;

        $order = $this->makeOrder($this->customer());
        $this->assertSame($before - 1, $product->fresh()->stock);

        $this->actingAs($this->customer());

        $this->post(route('orders.cancel', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('status');

        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame('cancelled', $this->paymentOf($order)->fresh()->status);
        $this->assertSame($before, $product->fresh()->stock);

        // Pembatalan kedua: tidak mengubah stok lagi.
        $this->post(route('orders.cancel', $order))->assertRedirect(route('orders.show', $order));
        $this->assertSame($before, $product->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->order_status);
    }

    public function test_cancel_rejected_while_qr_active_and_allowed_after_expiry(): void
    {
        $product = $this->product();
        $before = $product->stock;

        $order = $this->makeOrder($this->customer());
        $this->paymentOf($order)->update(['reference' => 'TRX-FAKE-AKTIF']);
        $order->update(['qr_expires_at' => now()->addHour()]);

        $this->actingAs($this->customer());

        $this->post(route('orders.cancel', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error');

        $this->assertSame('pending', $order->fresh()->order_status);
        $this->assertSame($before - 1, $product->fresh()->stock);

        // Detail menjelaskan QR masih aktif.
        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('kode QR pembayarannya masih aktif');

        // Setelah QR kedaluwarsa, pembatalan diizinkan.
        $order->update(['qr_expires_at' => now()->subMinute()]);

        $this->post(route('orders.cancel', $order))->assertSessionHas('status');
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame($before, $product->fresh()->stock);
    }

    public function test_cancel_rejected_for_paid_order_and_for_non_owner(): void
    {
        $product = $this->product();
        $before = $product->stock;

        $order = $this->makeOrder($this->customer());
        $this->setPaid($order);

        $this->actingAs($this->customer());
        $this->post(route('orders.cancel', $order))->assertRedirect(route('orders.show', $order));
        $this->assertSame('paid', $order->fresh()->order_status);
        $this->assertSame($before - 1, $product->fresh()->stock);

        $other = User::factory()->create(['role' => 'customer']);
        $foreign = $this->makeOrder($other);

        $this->post(route('orders.cancel', $foreign))->assertNotFound();
        $this->assertSame('pending', $foreign->fresh()->order_status);
    }

    public function test_admin_complete_only_from_paid(): void
    {
        $paid = $this->makeOrder($this->customer());
        $this->setPaid($paid);

        $pending = $this->makeOrder($this->customer());

        $this->actingAs($this->admin());

        $this->post(route('admin.orders.complete', $paid))
            ->assertRedirect(route('admin.orders.show', $paid))
            ->assertSessionHas('status');
        $this->assertSame('completed', $paid->fresh()->order_status);
        $this->assertSame('paid', $paid->fresh()->payment_status);

        $this->post(route('admin.orders.complete', $pending))
            ->assertRedirect(route('admin.orders.show', $pending))
            ->assertSessionHas('error');
        $this->assertSame('pending', $pending->fresh()->order_status);

        // Dari cancelled juga ditolak.
        $this->post(route('admin.orders.complete', $paid))
            ->assertSessionHas('error');
        $this->assertSame('completed', $paid->fresh()->order_status);
    }

    public function test_admin_cancel_uses_same_service_and_restores_stock(): void
    {
        $product = $this->product();
        $before = $product->stock;

        $order = $this->makeOrder($this->customer());

        $this->actingAs($this->admin());
        $this->post(route('admin.orders.cancel', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame('cancelled', $this->paymentOf($order)->fresh()->status);
        $this->assertSame($before, $product->fresh()->stock);
    }

    public function test_admin_orders_index_filters(): void
    {
        $own = $this->makeOrder($this->customer());
        $this->setPaid($own);

        $this->actingAs($this->admin());

        $this->get('/admin/pesanan?'.http_build_query(['q' => $this->customer()->email]))
            ->assertOk()
            ->assertSee($own->order_number);

        $this->get('/admin/pesanan?'.http_build_query(['order_status' => 'pending']))
            ->assertOk()
            ->assertDontSee($own->order_number);

        $this->get('/admin/pesanan?'.http_build_query(['payment_status' => 'paid']))
            ->assertOk()
            ->assertSee($own->order_number);
    }

    public function test_callback_paid_after_manual_cancel_is_ignored(): void
    {
        config()->set([
            'tripay.mode' => 'sandbox',
            'tripay.merchant_code' => 'FAKE-MERCHANT',
            'tripay.api_key' => 'FAKE-API-KEY',
            'tripay.private_key' => 'FAKE-PRIVATE-KEY',
            'tripay.qris_channel' => 'QRIS2',
            'tripay.expired_hours' => 24,
            'tripay.base_url' => 'https://tripay.co.id/api-sandbox/',
        ]);

        $product = $this->product();
        $before = $product->stock;

        $order = $this->makeOrder($this->customer());
        $payment = $this->paymentOf($order);
        $payment->update(['reference' => 'TRX-FAKE-BATAL']);
        $order->update(['qr_expires_at' => now()->subMinute()]);

        // Batal manual dulu: stok kembali, payment cancelled.
        $this->assertTrue(app(OrderService::class)->cancelOrder($order)['ok']);
        $this->assertSame($before, $product->fresh()->stock);

        // Callback PAID belakangan harus diabaikan.
        $data = [
            'reference' => 'TRX-FAKE-BATAL',
            'merchant_ref' => $order->order_number,
            'total_amount' => $order->total,
            'status' => 'PAID',
            'paid_at' => now()->timestamp,
        ];
        $json = json_encode($data);
        $signature = hash_hmac('sha256', $json, 'FAKE-PRIVATE-KEY');

        $this->call('POST', '/tripay/callback', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_SIGNATURE' => $signature,
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
        ], $json)->assertOk()->assertJson(['success' => true]);

        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame('cancelled', $payment->fresh()->status);
        $this->assertSame($before, $product->fresh()->stock);
        $this->assertSame('PAID', $payment->fresh()->payload['callback']['status']);
    }

    public function test_admin_dashboard_shows_correct_figures(): void
    {
        $paid = $this->makeOrder($this->customer());
        $this->setPaid($paid);

        $completed = $this->makeOrder($this->customer());
        $this->setPaid($completed);
        $completed->update(['order_status' => 'completed']);

        $this->makeOrder($this->customer());

        $low = $this->product('zoom-pro-1-bulan');
        $low->update(['stock' => 3]);

        $expectedRevenue = $paid->total + $completed->total;

        $this->actingAs($this->admin());

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Rp'.number_format($expectedRevenue, 0, ',', '.'))
            ->assertSee($low->name)
            ->assertSee($paid->order_number)
            ->assertSee($completed->order_number);
    }

    public function test_dashboard_links_match_role(): void
    {
        $this->actingAs($this->admin());
        $this->get('/dashboard')->assertOk()->assertSee('Panel admin');

        $this->actingAs($this->customer());
        $this->get('/dashboard')->assertOk()->assertSee('Riwayat pesanan');
    }
}
