<?php

namespace Tests\Feature;

use App\Livewire\PaymentPage;
use App\Livewire\ProductDetail;
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
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tes pola referensi: strip langkah katalog, pengelompokan
 * durasi lain, struk + status pengiriman di detail pesanan,
 * baris transparansi halaman bayar, dan tab filter pesanan.
 * Semua copy dan pola adalah buatan sendiri; tidak ada aset
 * atau teks dari situs referensi yang dipakai.
 */
class ReferencePatternsTest extends TestCase
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

    private function product(string $slug): Product
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

    private function setPaid(Order $order): void
    {
        $order->update(['order_status' => 'paid', 'payment_status' => 'paid', 'paid_at' => now()]);
        Payment::query()->where('order_id', $order->id)->orderBy('id')->firstOrFail()
            ->update(['status' => 'paid', 'paid_at' => now()]);
    }

    public function test_product_detail_typography_is_single_mono_family(): void
    {
        // Seluruh UI memakai satu keluarga font monospace
        // (Courier Prime): judul detail tebal dan paragraf
        // deskripsi memakai font yang sama, sesuai gambar
        // referensi yang memakai satu keluarga font mesin tik
        // untuk semua teks.
        Livewire::test(ProductDetail::class, ['product' => $this->product('netflix-premium-1-bulan')])
            ->assertOk()
            ->assertSeeHtml('<h1 class="mt-1 text-3xl font-bold leading-9 text-ink">')
            ->assertSeeHtml('<p class="mt-1 font-sans text-base leading-7 text-ink">');
    }

    public function test_no_view_or_config_references_old_fonts(): void
    {
        // Tidak ada lagi referensi Archivo/Georgia atau kelas
        // font-serif di view, CSS, maupun konfigurasi Tailwind.
        // Courier Prime adalah satu-satunya keluarga font.
        $config = file_get_contents(base_path('tailwind.config.js'));
        $css = file_get_contents(base_path('resources/css/app.css'));

        $this->assertStringContainsString('Courier Prime', $config);
        $this->assertStringNotContainsString('Archivo', $config);
        $this->assertStringNotContainsString('Georgia', $config);
        $this->assertStringContainsString("@import '@fontsource/courier-prime/400.css'", $css);
        $this->assertStringContainsString("@import '@fontsource/courier-prime/700.css'", $css);
        $this->assertStringNotContainsString('archivo', $css);
        $this->assertStringNotContainsString('Georgia', $css);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('resources/views'))
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $contents = file_get_contents($file->getPathname());
                $this->assertStringNotContainsString('font-serif', $contents, $file->getPathname());
                $this->assertStringNotContainsString('Archivo', $contents, $file->getPathname());
                $this->assertStringNotContainsString('Georgia', $contents, $file->getPathname());
            }
        }
    }

    public function test_catalog_pills_use_semibold_and_ink_active_state(): void
    {
        Livewire::test(\App\Livewire\ProductList::class)
            ->assertOk()
            ->assertSeeHtml('text-sm font-semibold')
            ->assertSeeHtml('bg-ink text-white');
    }

    public function test_layouts_do_not_load_google_fonts(): void
    {
        // Courier Prime di-host sendiri; tidak ada lagi permintaan
        // Google Fonts dari layout mana pun.
        $this->get('/')->assertOk()->assertDontSee('fonts.googleapis.com');
        $this->get('/login')->assertOk()->assertDontSee('fonts.googleapis.com');
    }

    public function test_catalog_shows_four_step_strip_and_keeps_dummy_notice(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Cara berbelanja')
            ->assertSee('Pilih produk')
            ->assertSee('Isi nama dan email')
            ->assertSee('Scan QRIS')
            ->assertSee('Pantau di Pesanan Saya')
            ->assertSee('Katalog contoh untuk latihan. Produk di halaman ini data dummy lokal, bukan akun sungguhan dan tidak dijual.');
    }

    public function test_product_base_name_strips_duration_suffix_only_at_the_end(): void
    {
        $this->assertSame('Netflix Premium', $this->product('netflix-premium-1-bulan')->baseName());
        $this->assertSame('Netflix Premium', $this->product('netflix-premium-3-bulan')->baseName());
        $this->assertSame('Canva Pro', $this->product('canva-pro-1-tahun')->baseName());
        $this->assertSame('Microsoft 365 Personal', $this->product('microsoft-365-personal-1-bulan')->baseName());
        $this->assertTrue($this->product('netflix-premium-1-bulan')->hasDurationSuffix());
        $this->assertTrue($this->product('canva-pro-1-tahun')->hasDurationSuffix());
    }

    public function test_product_detail_shows_duration_variants_sorted_by_price(): void
    {
        Livewire::test(ProductDetail::class, ['product' => $this->product('netflix-premium-1-bulan')])
            ->assertOk()
            ->assertSee('Durasi lain dari Netflix Premium')
            ->assertSee('Netflix Premium 3 Bulan')
            ->assertSee('Rp189.000')
            ->assertDontSee('Spotify Premium 1 Bulan');

        Livewire::test(ProductDetail::class, ['product' => $this->product('canva-pro-1-tahun')])
            ->assertOk()
            ->assertSee('Durasi lain dari Canva Pro')
            ->assertSee('Canva Pro 1 Bulan');
    }

    public function test_product_detail_without_sibling_hides_duration_section(): void
    {
        Livewire::test(ProductDetail::class, ['product' => $this->product('youtube-premium-1-bulan')])
            ->assertOk()
            ->assertDontSee('Durasi lain');
    }

    public function test_order_detail_shows_receipt_block_and_honest_delivery_status(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Struk pesanan')
            ->assertSee($order->order_number)
            ->assertSee('WIB')
            ->assertSee('Netflix Premium 1 Bulan')
            ->assertSee('Rp69.000')
            ->assertSee('Metode pembayaran')
            ->assertSee('QRIS2')
            ->assertSee('Referensi Tripay')
            ->assertSee('Waktu bayar')
            ->assertSee('Belum dibayar')
            ->assertSee('Status pengiriman')
            ->assertSee('Menunggu pembayaran')
            ->assertDontSee('sudah dikirim ke email tujuan');

        $this->setPaid($order);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Pembayaran diterima')
            ->assertSee('Status pengiriman')
            ->assertDontSee('Belum dibayar');

        $order->update(['order_status' => 'completed']);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('sudah dikirim ke email tujuan');
    }

    public function test_payment_page_shows_transparency_row_without_inventing_fee(): void
    {
        config()->set([
            'tripay.merchant_code' => '',
            'tripay.api_key' => '',
            'tripay.private_key' => '',
        ]);
        Http::fake();

        $user = $this->customer();
        $order = $this->makeOrder($user);

        Livewire::actingAs($user)
            ->test(PaymentPage::class, ['orderId' => $order->id])
            ->assertOk()
            ->assertSee('Rincian pembayaran')
            ->assertSee('Nominal yang harus dibayar: Rp69.000')
            ->assertSee('Bayar tepat sebesar nominal ini; jangan ditambah atau dikurangi.')
            ->assertSee('Kredensial Tripay sandbox belum diisi')
            ->assertDontSee('Biaya layanan');

        Http::assertNothingSent();
    }

    public function test_payment_page_shows_stored_merchant_fee_as_seller_cost(): void
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

        Http::fake(function (ClientRequest $request) {
            $url = $request->url();

            if (str_contains($url, 'transaction/create')) {
                $data = $request->data();

                return Http::response([
                    'success' => true,
                    'message' => '',
                    'data' => [
                        'reference' => 'T0001FAKE1234',
                        'merchant_ref' => (string) ($data['merchant_ref'] ?? 'FAKE-REF'),
                        'payment_method' => 'QRIS2',
                        'amount' => (int) ($data['amount'] ?? 0),
                        'fee_merchant' => 1000,
                        'fee_customer' => 0,
                        'total_fee' => 1000,
                        'amount_received' => (int) ($data['amount'] ?? 0) - 1000,
                        'status' => 'UNPAID',
                        'expired_time' => now()->addDay()->timestamp,
                        'qr_string' => 'FAKE-QR-STRING-0001',
                        'qr_url' => 'https://tripay.example.test/qr/FAKE.png',
                        'instructions' => [],
                    ],
                ], 200);
            }

            return Http::response(['success' => false, 'message' => 'Tidak tersedia'], 200);
        });

        $user = $this->customer();
        $order = $this->makeOrder($user);

        Livewire::actingAs($user)
            ->test(PaymentPage::class, ['orderId' => $order->id])
            ->assertOk()
            ->assertSee('Nominal yang harus dibayar: Rp69.000')
            ->assertSee('Bayar tepat sebesar nominal ini; jangan ditambah atau dikurangi.')
            ->assertSee('Berlaku sampai')
            ->assertSee('WIB')
            ->assertSee('Biaya layanan Rp1.000 ditanggung penjual');
    }

    public function test_customer_order_tabs_filter_by_status_and_show_counts(): void
    {
        $user = $this->customer();
        $pending = $this->makeOrder($user);
        $paid = $this->makeOrder($user, 'spotify-premium-1-bulan');
        $this->setPaid($paid);
        $cancelled = $this->makeOrder($user, 'canva-pro-1-bulan');
        $this->assertTrue(app(OrderService::class)->cancelOrder($cancelled)['ok']);

        $this->actingAs($user)
            ->get('/pesanan')
            ->assertOk()
            ->assertSee('Semua (3)')
            ->assertSee('Menunggu bayar (1)')
            ->assertSee('Lunas (1)')
            ->assertSee('Selesai (0)')
            ->assertSee('Dibatalkan (1)')
            ->assertSee($pending->order_number)
            ->assertSee($paid->order_number)
            ->assertSee($cancelled->order_number);

        $this->actingAs($user)
            ->get('/pesanan?order_status=paid')
            ->assertOk()
            ->assertSee($paid->order_number)
            ->assertDontSee($pending->order_number)
            ->assertDontSee($cancelled->order_number);

        $this->actingAs($user)
            ->get('/pesanan?order_status=cancelled')
            ->assertOk()
            ->assertSee($cancelled->order_number)
            ->assertDontSee($paid->order_number);
    }

    public function test_admin_order_tabs_filter_by_status(): void
    {
        $user = $this->customer();
        $pending = $this->makeOrder($user);
        $completed = $this->makeOrder($user, 'spotify-premium-1-bulan');
        $this->setPaid($completed);
        $completed->update(['order_status' => 'completed']);

        $this->actingAs($this->admin())
            ->get('/admin/pesanan')
            ->assertOk()
            ->assertSee('Semua (2)')
            ->assertSee('Menunggu bayar (1)')
            ->assertSee('Selesai (1)');

        $this->actingAs($this->admin())
            ->get('/admin/pesanan?order_status=completed')
            ->assertOk()
            ->assertSee($completed->order_number)
            ->assertDontSee($pending->order_number);
    }
}
