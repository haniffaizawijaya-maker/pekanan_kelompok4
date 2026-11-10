<?php

namespace Tests\Feature;

use App\Livewire\PaymentPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\TripayService;
use Carbon\Carbon;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tes pengunci perbaikan blocker dan mayor audit produksi:
 * B1 (riwayat order bertahan saat akun dihapus), M1 (waktu
 * kedaluwarsa tampil dalam WIB), M2 (email terverifikasi dan
 * mailer log), M3 (kunci pembuatan transaksi Tripay), dan
 * M4 (seeder menolak environment production). M5 (REFUND)
 * diuji di TripayPaymentTest yang memakai ulang helper
 * callback di sana.
 */
class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([UserSeeder::class, CategorySeeder::class, ProductSeeder::class]);

        config()->set([
            'tripay.mode' => 'sandbox',
            'tripay.merchant_code' => 'FAKE-MERCHANT',
            'tripay.api_key' => 'FAKE-API-KEY',
            'tripay.private_key' => 'FAKE-PRIVATE-KEY',
            'tripay.qris_channel' => 'QRIS2',
            'tripay.expired_hours' => 24,
            'tripay.base_url' => 'https://tripay.co.id/api-sandbox/',
        ]);
    }

    private function customer(): User
    {
        return User::where('email', 'customer@minicom.test')->firstOrFail();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@minicom.test')->firstOrFail();
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

    // B1: menghapus akun tidak menghapus riwayat order.
    public function test_deleting_account_keeps_order_and_admin_pages_work(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertNull(User::find($user->id));
        $this->assertSame(1, Order::query()->count());

        $kept = Order::query()->firstOrFail();
        $this->assertNull($kept->user_id);
        $this->assertSame($order->order_number, $kept->order_number);
        $this->assertSame('Pelanggan Sudoy', $kept->buyer_name);
        $this->assertSame('customer@minicom.test', $kept->buyer_email);

        // Halaman admin tetap tampil dengan snapshot pembeli.
        $this->actingAs($this->admin())
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Pelanggan Sudoy');

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $kept))
            ->assertOk()
            ->assertSee('Pelanggan Sudoy')
            ->assertSee('Akun sudah dihapus');
    }

    public function test_deleting_account_without_orders_works(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertNull(User::find($user->id));
    }

    // M1: waktu kedaluwarsa tampil dalam WIB (Asia/Jakarta).
    public function test_payment_expiry_is_displayed_in_wib(): void
    {
        // Kredensial FAKE dari setUp dipertahankan agar halaman
        // menampilkan bagian kedaluwarsa; HTTP dipalsukan agar
        // tidak ada pemanggilan jaringan sungguhan.
        Http::fake();

        $user = $this->customer();
        $order = $this->makeOrder($user);
        $order->payments()->firstOrFail()->update(['reference' => 'FAKE-REF']);
        // Unix timestamp untuk 1 Nov 2026 00:00 UTC, di masa
        // depan terhadap jam tes. Alur Tripay mengubahnya lewat
        // createFromTimestamp dengan zona aplikasi, jadi
        // tersimpan dan tampil sebagai 07:00 WIB setelah zona
        // diubah ke Asia/Jakarta. (createFromTimestamp tanpa
        // zona mengembalikan UTC, sehingga jam UTC akan salah
        // tampil sebagai WIB.)
        $order->update(['qr_expires_at' => Carbon::createFromTimestamp(
            Carbon::create(2026, 11, 1, 0, 0, 0, 'UTC')->timestamp,
            config('app.timezone')
        )]);

        // 00:00 UTC sama dengan 07:00 WIB.
        Livewire::actingAs($user)
            ->test(PaymentPage::class, ['orderId' => $order->id])
            ->assertSee('01/11/2026 07:00');
    }

    // M2: checkout, riwayat, dan halaman bayar mensyaratkan
    // email terverifikasi.
    public function test_unverified_user_is_redirected_to_email_verification(): void
    {
        $user = User::factory()->unverified()->create();
        $order = $this->makeOrder($user);

        $this->actingAs($user)->get('/checkout')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get('/pesanan')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('orders.pay', $order))->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_open_checkout_with_items_in_cart(): void
    {
        $user = $this->customer();
        app(CartService::class)->add($user, $this->product(), 1);

        $this->actingAs($user)->get('/checkout')->assertOk();
    }

    // M2: dengan mailer log, tautan verifikasi ditulis ke file log.
    public function test_verification_link_is_written_to_log_with_log_mailer(): void
    {
        $path = storage_path('logs/verify-link-test.log');

        if (file_exists($path)) {
            unlink($path);
        }

        config()->set([
            'mail.default' => 'log',
            'logging.default' => 'single',
            'logging.channels.single.path' => $path,
        ]);

        try {
            $user = User::factory()->unverified()->create();
            $user->sendEmailVerificationNotification();

            $this->assertFileExists($path);
            $this->assertStringContainsString('verify-email', (string) file_get_contents($path));
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    // M3: dua pemanggilan berurutan hanya membuat satu transaksi.
    public function test_concurrent_create_calls_reuse_the_same_reference(): void
    {
        $calls = 0;

        Http::fake(function (ClientRequest $request) use (&$calls) {
            if (! str_contains($request->url(), 'transaction/create')) {
                return Http::response(['success' => false], 404);
            }

            $calls++;
            $data = $request->data();

            return Http::response([
                'success' => true,
                'message' => '',
                'data' => [
                    'reference' => 'T0001FAKE-REF',
                    'merchant_ref' => $data['merchant_ref'],
                    'qr_url' => 'https://tripay.example.test/qr/FAKE.png',
                    'qr_string' => 'FAKE-QR-STRING',
                    'status' => 'UNPAID',
                    'expired_time' => now()->addDay()->timestamp,
                ],
            ], 200);
        });

        $order = $this->makeOrder($this->customer());
        $service = app(TripayService::class);

        $first = $service->createClosedTransaction($order, $order->order_number);
        $second = $service->createClosedTransaction($order->fresh(), $order->order_number);

        $this->assertTrue($first['ok']);
        $this->assertTrue($second['ok']);
        $this->assertSame('T0001FAKE-REF', $first['payment']->reference);
        $this->assertSame('T0001FAKE-REF', $second['payment']->reference);
        $this->assertSame(1, $calls);
    }

    // M3: bila kunci sedang dipegang proses lain, pemanggilan
    // gagal dengan jujur tanpa HTTP baru dan tanpa crash.
    public function test_create_returns_honest_error_when_creation_lock_is_held(): void
    {
        Http::fake();

        $order = $this->makeOrder($this->customer());
        $lock = Cache::lock('tripay-create-order-'.$order->id, 30);
        $this->assertTrue($lock->acquire());

        try {
            $result = app(TripayService::class)->createClosedTransaction($order, $order->order_number, 0);

            $this->assertFalse($result['ok']);
            $this->assertStringContainsString('sedang diproses', $result['message']);
        } finally {
            $lock->release();
        }

        Http::assertSentCount(0);
    }

    // M4: seeder menolak environment production.
    public function test_user_seeder_refuses_production_environment(): void
    {
        $original = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('tidak boleh dijalankan di production');

            (new UserSeeder)->run();
        } finally {
            app()->detectEnvironment(fn () => $original);
        }
    }

    public function test_database_seeder_refuses_production_environment(): void
    {
        $original = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('tidak boleh dijalankan di production');

            (new DatabaseSeeder)->run();
        } finally {
            app()->detectEnvironment(fn () => $original);
        }

        $this->assertSame(2, User::query()->count());
    }
}
