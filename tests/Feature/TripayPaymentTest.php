<?php

namespace Tests\Feature;

use App\Livewire\PaymentPage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\TripayService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tes Fase 4: Tripay QRIS2 tanpa jaringan sungguhan.
 * Semua respons Tripay dipalsukan dengan Http::fake, dan semua
 * kunci memakai nilai FAKE yang jelas bukan kunci asli. Satu
 * pengecualian adalah vektor signature dari contoh dokumen
 * resmi Tripay (kunci contoh publik di halaman developer),
 * yang dipakai untuk mengunci rumus signature secara independen.
 */
class TripayPaymentTest extends TestCase
{
    use RefreshDatabase;

    /** State bersama untuk stub Tripay: Http::fake() menumpuk
     *  stub, bukan menggantinya, jadi closure stub membaca state
     *  yang bisa diubah antar pemanggilan fakeTripay dalam satu tes.
     */
    private object $tripayState;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([UserSeeder::class, CategorySeeder::class, ProductSeeder::class]);

        $this->tripayState = (object) [
            'createOk' => true,
            'detailStatus' => 'UNPAID',
            'channels' => [
                ['code' => 'QRIS2', 'name' => 'QRIS', 'active' => true],
                ['code' => 'BRIVA', 'name' => 'BRI Virtual Account', 'active' => true],
            ],
            'instructionOk' => true,
            'qrUrl' => 'https://tripay.example.test/qr/FAKE.png',
            'qrString' => 'FAKE-QR-STRING-0001',
            'createCalls' => null,
        ];

        $this->setFakeConfig();
    }

    private function setFakeConfig(array $overrides = []): void
    {
        config()->set(array_merge([
            'tripay.mode' => 'sandbox',
            'tripay.merchant_code' => 'FAKE-MERCHANT',
            'tripay.api_key' => 'FAKE-API-KEY',
            'tripay.private_key' => 'FAKE-PRIVATE-KEY',
            'tripay.qris_channel' => 'QRIS2',
            'tripay.expired_hours' => 24,
            'tripay.base_url' => 'https://tripay.co.id/api-sandbox/',
        ], $overrides));
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

        $created = app(\App\Services\OrderService::class)->createOrder(
            $user,
            $user->name,
            $user->email,
            null
        );

        $this->assertTrue($created['ok'], $created['message']);
        $this->assertNotNull($created['order']);

        return $created['order'];
    }

    /**
     * Respons Tripay dipalsukan per endpoint. Respons create
     * dibuat dinamis dari merchant_ref yang dikirim, supaya
     * percobaan ulang (-R2) menghasilkan referensi berbeda.
     * Opsi yang diberikan menimpa state stub (termasuk nilai
     * null yang eksplisit untuk qrUrl/qrString).
     */
    private function fakeTripay(array $options = []): void
    {
        foreach ($options as $key => $value) {
            $this->tripayState->{$key} = $value;
        }

        $state = $this->tripayState;

        Http::fake(function (ClientRequest $request) use ($state) {
            $url = $request->url();

            if (str_contains($url, 'transaction/create')) {
                if (is_object($state->createCalls)) {
                    $state->createCalls->count++;
                }

                if (! $state->createOk) {
                    return Http::response(['success' => false, 'message' => 'Channel tidak aktif'], 200);
                }

                $data = $request->data();
                $merchantRef = (string) ($data['merchant_ref'] ?? 'FAKE-REF');
                $amount = (int) ($data['amount'] ?? 0);

                return Http::response([
                    'success' => true,
                    'message' => '',
                    'data' => [
                        'reference' => 'T0001FAKE'.strtoupper(substr(md5($merchantRef), 0, 10)),
                        'merchant_ref' => $merchantRef,
                        'payment_method' => 'QRIS2',
                        'payment_name' => 'QRIS',
                        'customer_name' => $data['customer_name'] ?? 'Pelanggan',
                        'customer_email' => $data['customer_email'] ?? 'pelanggan@example.test',
                        'amount' => $amount,
                        'fee_merchant' => 1000,
                        'fee_customer' => 0,
                        'total_fee' => 1000,
                        'amount_received' => $amount - 1000,
                        'pay_code' => $state->qrString,
                        'pay_url' => null,
                        'checkout_url' => 'https://tripay.example.test/checkout/FAKE',
                        'status' => 'UNPAID',
                        'expired_time' => now()->addDay()->timestamp,
                        'qr_string' => $state->qrString,
                        'qr_url' => $state->qrUrl,
                        'instructions' => [],
                    ],
                ], 200);
            }

            if (str_contains($url, 'transaction/detail')) {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

                return Http::response([
                    'success' => true,
                    'message' => '',
                    'data' => [
                        'reference' => (string) ($query['reference'] ?? 'T0001FAKE'),
                        'merchant_ref' => 'FAKE-REF',
                        'payment_method' => 'QRIS2',
                        'amount' => 69000,
                        'fee_merchant' => 1000,
                        'total_fee' => 1000,
                        'status' => $state->detailStatus,
                        'paid_at' => $state->detailStatus === 'PAID' ? (string) now()->timestamp : null,
                        'expired_time' => now()->addDay()->timestamp,
                        'qr_string' => $state->qrString,
                        'qr_url' => $state->qrUrl,
                    ],
                ], 200);
            }

            if (str_contains($url, 'merchant/payment-channel')) {
                return Http::response([
                    'success' => true,
                    'message' => 'Success',
                    'data' => $state->channels,
                ], 200);
            }

            if (str_contains($url, 'payment/instruction')) {
                if (! $state->instructionOk) {
                    return Http::response(['success' => false, 'message' => 'Invalid API Key'], 200);
                }

                return Http::response([
                    'success' => true,
                    'message' => '',
                    'data' => [
                        [
                            'title' => 'Semua Aplikasi QRIS',
                            'steps' => [
                                'Buka <b>aplikasi</b> pembayaran yang mendukung QRIS',
                                'Pindai kode QR yang tampil',
                                'Periksa nominal lalu konfirmasi pembayaran',
                            ],
                        ],
                    ],
                ], 200);
            }

            return Http::response(['success' => false, 'message' => 'Endpoint tidak dipalsukan'], 404);
        });
    }

    private function callbackData(Order $order, Payment $payment, string $status, array $overrides = []): array
    {
        return array_merge([
            'reference' => $payment->reference,
            'merchant_ref' => $payment->merchant_ref,
            'payment_method' => 'QRIS',
            'payment_method_code' => 'QRIS2',
            'total_amount' => $order->total,
            'fee_merchant' => 1000,
            'fee_customer' => 0,
            'total_fee' => 1000,
            'amount_received' => $order->total - 1000,
            'is_closed_payment' => 1,
            'status' => $status,
            'paid_at' => $status === 'PAID' ? now()->timestamp : null,
            'note' => null,
        ], $overrides);
    }

    private function postCallback(array $data, ?string $signature = null, ?string $event = 'payment_status')
    {
        $json = json_encode($data);
        $privateKey = (string) config('tripay.private_key');
        $sig = $signature ?? hash_hmac('sha256', $json, $privateKey);

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_SIGNATURE' => $sig,
        ];

        if ($event !== null) {
            $server['HTTP_X_CALLBACK_EVENT'] = $event;
        }

        return $this->call('POST', '/tripay/callback', [], [], [], $server, $json);
    }

    public function test_create_signature_matches_official_docs_vector(): void
    {
        // Vektor dari contoh dokumen resmi Tripay (kunci contoh
        // publik, bukan kunci asli): T0001 + INV55567 + 1500000.
        $this->setFakeConfig([
            'tripay.merchant_code' => 'T0001',
            'tripay.private_key' => 'ytf6ooi2gmlNPfpchd94jDOk8hRWOu',
        ]);

        $service = app(TripayService::class);

        $this->assertSame(
            '9f167eba844d1fcb369404e2bda53702e2f78f7aa12e91da6715414e65b8c86a',
            $service->createSignature('INV55567', 1500000)
        );

        // Vektor FAKE independen: hash dihitung di tes dari string
        // gabungan yang sama, tanpa memakai method service.
        $this->setFakeConfig();

        $this->assertSame(
            hash_hmac('sha256', 'FAKE-MERCHANT'.'FAKE-REF-1'.'12345', 'FAKE-PRIVATE-KEY'),
            $service->createSignature('FAKE-REF-1', 12345)
        );
    }

    public function test_callback_signature_uses_raw_json_body(): void
    {
        $service = app(TripayService::class);
        $json = '{"reference":"T1","status":"PAID"}';

        $this->assertSame(
            hash_hmac('sha256', $json, 'FAKE-PRIVATE-KEY'),
            $service->callbackSignature($json)
        );
        $this->assertTrue($service->verifyCallbackSignature(
            hash_hmac('sha256', $json, 'FAKE-PRIVATE-KEY'),
            $json
        ));
        $this->assertFalse($service->verifyCallbackSignature('salah', $json));
        $this->assertFalse($service->verifyCallbackSignature(null, $json));
    }

    public function test_create_closed_transaction_saves_payment_and_sends_qris2_payload(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay();

        $result = app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $this->assertTrue($result['ok'], $result['message']);

        Http::assertSent(function (ClientRequest $request) use ($order, $user) {
            if (! str_contains($request->url(), 'transaction/create')) {
                return false;
            }

            $data = $request->data();

            return $data['method'] === 'QRIS2'
                && $data['merchant_ref'] === $order->order_number
                && (int) $data['amount'] === $order->total
                && $data['signature'] === hash_hmac('sha256', 'FAKE-MERCHANT'.$order->order_number.$order->total, 'FAKE-PRIVATE-KEY')
                && str_contains((string) $data['callback_url'], '/tripay/callback')
                && str_contains((string) $data['return_url'], '/pesanan/'.$order->id)
                && (int) $data['expired_time'] > now()->timestamp
                && $data['customer_name'] === $user->name
                && $data['customer_email'] === $user->email
                && $data['order_items'][0]['name'] === 'Netflix Premium 1 Bulan'
                && (int) $data['order_items'][0]['price'] === 69000;
        });

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertNotNull($payment->reference);
        $this->assertSame('https://tripay.example.test/qr/FAKE.png', $payment->qr_url);
        $this->assertSame($order->order_number, $payment->merchant_ref);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('FAKE-QR-STRING-0001', $payment->payload['data']['qr_string']);
        $this->assertSame(1000, $payment->payload['data']['total_fee']);

        $fresh = $order->fresh();
        $this->assertSame($payment->reference, $fresh->tripay_reference);
        $this->assertSame($payment->reference, $fresh->gateway_reference);
        $this->assertNotNull($fresh->qr_expires_at);
        $this->assertSame('pending', $fresh->order_status);
        $this->assertSame('unpaid', $fresh->payment_status);
        $this->assertNull($fresh->paid_at);
    }

    public function test_create_fails_gracefully_when_tripay_returns_error(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay(['createOk' => false]);

        $result = app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $this->assertFalse($result['ok']);
        $this->assertNotSame('', $result['message']);

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertNull($payment->reference);
        $this->assertNull($payment->qr_url);
        $this->assertSame('pending', $payment->status);
        $this->assertSame(1, Order::count());
    }

    public function test_create_fails_without_credentials_and_sends_no_request(): void
    {
        $this->setFakeConfig([
            'tripay.merchant_code' => '',
            'tripay.api_key' => '',
            'tripay.private_key' => '',
        ]);
        Http::fake();

        $user = $this->customer();
        $order = $this->makeOrder($user);

        $result = app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }

    public function test_callback_paid_marks_order_paid_and_is_idempotent(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay();

        app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $stockAfterOrder = $this->product('netflix-premium-1-bulan')->fresh()->stock;
        $this->assertSame(23, $stockAfterOrder);

        $data = $this->callbackData($order, $payment, 'PAID');

        $this->postCallback($data)
            ->assertOk()
            ->assertJson(['success' => true]);

        $payment = $payment->fresh();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('PAID', $payment->payload['callback']['status']);

        $fresh = $order->fresh();
        $this->assertSame('paid', $fresh->order_status);
        $this->assertSame('paid', $fresh->payment_status);
        $this->assertNotNull($fresh->paid_at);

        // Callback yang sama kedua kali: tidak ada perubahan ganda.
        $this->postCallback($data)->assertOk()->assertJson(['success' => true]);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(23, $this->product('netflix-premium-1-bulan')->fresh()->stock);
    }

    public function test_callback_rejects_bad_signature_without_changing_data(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay();

        app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $data = $this->callbackData($order, $payment, 'PAID');

        $this->postCallback($data, signature: 'signature-palsu-yang-salah')
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->assertSame(23, $this->product('netflix-premium-1-bulan')->fresh()->stock);
    }

    public function test_callback_ignores_amount_mismatch(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay();

        app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $data = $this->callbackData($order, $payment, 'PAID', ['total_amount' => $order->total + 1000]);

        $this->postCallback($data)->assertOk()->assertJson(['success' => true]);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_callback_expired_restocks_stock_only_once(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay();

        app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame(23, $this->product('netflix-premium-1-bulan')->fresh()->stock);

        $data = $this->callbackData($order, $payment, 'EXPIRED');

        $this->postCallback($data)->assertOk()->assertJson(['success' => true]);

        $this->assertSame('expired', $payment->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame(24, $this->product('netflix-premium-1-bulan')->fresh()->stock);

        // Callback EXPIRED kedua tidak mengembalikan stok lagi.
        $this->postCallback($data)->assertOk()->assertJson(['success' => true]);

        $this->assertSame(24, $this->product('netflix-premium-1-bulan')->fresh()->stock);
    }

    public function test_callback_failed_restocks_and_cancels_order(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay();

        app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $data = $this->callbackData($order, $payment, 'FAILED');

        $this->postCallback($data)->assertOk()->assertJson(['success' => true]);

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(24, $this->product('netflix-premium-1-bulan')->fresh()->stock);
    }

    public function test_callback_refund_marks_payment_refunded_without_changing_order(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay();

        app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

        $this->postCallback($this->callbackData($order, $payment, 'PAID'))->assertOk();
        $this->assertSame('paid', $payment->fresh()->status);

        $this->postCallback($this->callbackData($order, $payment, 'REFUND'))->assertOk()->assertJson(['success' => true]);

        // M5: payment menjadi refunded, tetapi order, payment_status,
        // dan stok tidak berubah; refund direkonsiliasi manual.
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->order_status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(23, $this->product('netflix-premium-1-bulan')->fresh()->stock);

        // REFUND kedua tidak mengubah apa pun lagi.
        $this->postCallback($this->callbackData($order, $payment, 'REFUND'))->assertOk();
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame(23, $this->product('netflix-premium-1-bulan')->fresh()->stock);
    }

    public function test_callback_refund_with_bad_signature_is_rejected(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay();

        app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

        $this->postCallback($this->callbackData($order, $payment, 'PAID'))->assertOk();

        $this->postCallback($this->callbackData($order, $payment, 'REFUND'), 'bad-signature')->assertForbidden();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->order_status);
    }

    public function test_refund_notice_is_shown_on_customer_and_admin_order_pages(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay();

        app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

        $this->postCallback($this->callbackData($order, $payment, 'PAID'))->assertOk();
        $this->postCallback($this->callbackData($order, $payment, 'REFUND'))->assertOk();

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('telah dikembalikan');

        $this->actingAs(User::where('email', 'admin@minicom.test')->firstOrFail())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Dikembalikan')
            ->assertSee('rekonsiliasi dilakukan manual');
    }

    public function test_callback_unknown_merchant_ref_is_ignored(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);

        $data = [
            'reference' => 'T0001TIDAKADA',
            'merchant_ref' => 'MC-TIDAK-ADA',
            'payment_method' => 'QRIS',
            'payment_method_code' => 'QRIS2',
            'total_amount' => $order->total,
            'fee_merchant' => 0,
            'fee_customer' => 0,
            'total_fee' => 0,
            'amount_received' => $order->total,
            'is_closed_payment' => 1,
            'status' => 'PAID',
            'paid_at' => now()->timestamp,
            'note' => null,
        ];

        $this->postCallback($data)->assertOk()->assertJson(['success' => true]);

        $this->assertSame('pending', Payment::query()->firstOrFail()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_callback_non_payment_status_event_is_ignored(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay();

        app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $data = $this->callbackData($order, $payment, 'PAID');

        $this->postCallback($data, event: 'other_event')->assertOk()->assertJson(['success' => true]);

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_callback_route_rejects_get_requests(): void
    {
        $this->get('/tripay/callback')->assertStatus(405);
    }

    public function test_check_status_paid_via_transaction_detail(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay(['detailStatus' => 'PAID']);

        app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

        // Fixture detail memakai amount 69000; order test ini juga
        // 1 x Netflix Rp69.000 sehingga nominalnya cocok.
        $result = app(TripayService::class)->checkStatus($payment);

        $this->assertTrue($result['ok']);
        $this->assertSame('paid', $result['outcome']);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_check_status_unpaid_stays_pending(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay(['detailStatus' => 'UNPAID']);

        app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

        $result = app(TripayService::class)->checkStatus($payment);

        $this->assertTrue($result['ok']);
        $this->assertSame('ignored', $result['outcome']);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_merchant_payment_channel_detects_qris2_availability(): void
    {
        $service = app(TripayService::class);

        $this->fakeTripay();
        $this->assertTrue($service->isQrisChannelAvailable());

        $this->fakeTripay(['channels' => [
            ['code' => 'QRIS2', 'name' => 'QRIS', 'active' => false],
            ['code' => 'QRIS', 'name' => 'QRIS by ShopeePay', 'active' => true],
        ]]);
        $this->assertFalse($service->isQrisChannelAvailable());

        $this->fakeTripay(['channels' => [
            ['code' => 'BRIVA', 'name' => 'BRI Virtual Account', 'active' => true],
        ]]);
        $this->assertFalse($service->isQrisChannelAvailable());
    }

    public function test_payment_page_owner_sees_qr_and_reuses_reference_on_reload(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $calls = (object) ['count' => 0];
        $this->fakeTripay(['createCalls' => $calls]);

        Livewire::actingAs($user)
            ->test(PaymentPage::class, ['orderId' => $order->id])
            ->assertOk()
            ->assertSee('Pembayaran')
            ->assertSee($order->order_number)
            ->assertSee('Kode QR QRIS')
            ->assertSee('https://tripay.example.test/qr/FAKE.png')
            ->assertSee('Rp69.000')
            ->assertSee('Berlaku sampai')
            ->assertSee('Buka aplikasi pembayaran yang mendukung QRIS')
            ->assertSee('Cek status pembayaran')
            ->assertSee('Kembali ke detail pesanan');

        $this->assertSame(1, $calls->count);
        $this->assertNotNull(Payment::query()->firstOrFail()->reference);

        // Muat ulang: referensi dipakai ulang, tidak ada create baru.
        Livewire::actingAs($user)
            ->test(PaymentPage::class, ['orderId' => $order->id])
            ->assertOk()
            ->assertSee('Kode QR QRIS');

        $this->assertSame(1, $calls->count);
    }

    public function test_payment_page_qr_string_only_shows_string_without_image(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay(['qrUrl' => null, 'qrString' => 'FAKEQRSTRINGHANYA']);

        $component = Livewire::actingAs($user)
            ->test(PaymentPage::class, ['orderId' => $order->id])
            ->assertOk()
            ->assertSee('FAKEQRSTRINGHANYA');

        $this->assertNull(Payment::query()->firstOrFail()->qr_url);
        $this->assertStringNotContainsString(
            '<img',
            $component->html()
        );
    }

    public function test_payment_page_instruction_failure_uses_generic_steps(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay(['instructionOk' => false]);

        Livewire::actingAs($user)
            ->test(PaymentPage::class, ['orderId' => $order->id])
            ->assertOk()
            ->assertSee('Pindai kode QR di atas.')
            ->assertSee('Konfirmasi pembayaran di aplikasi tersebut.');
    }

    public function test_payment_page_shows_credentials_message_without_keys_and_sends_no_request(): void
    {
        $this->setFakeConfig([
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
            ->assertSee('Kredensial Tripay sandbox belum diisi')
            ->assertSee('TRIPAY_MERCHANT_CODE')
            ->assertSee('TRIPAY_API_KEY')
            ->assertSee('TRIPAY_PRIVATE_KEY');

        Http::assertNothingSent();
        $this->assertNull(Payment::query()->firstOrFail()->reference);
    }

    public function test_payment_page_retry_after_failed_create(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay(['createOk' => false]);

        $component = Livewire::actingAs($user)
            ->test(PaymentPage::class, ['orderId' => $order->id])
            ->assertOk()
            ->assertSee('Coba buat kode QR lagi');

        $this->assertNull(Payment::query()->firstOrFail()->reference);

        $this->fakeTripay(['createOk' => true]);

        $component->call('retryCreate')
            ->assertSee('Kode QR QRIS')
            ->assertSee('https://tripay.example.test/qr/FAKE.png');

        $this->assertNotNull(Payment::query()->firstOrFail()->fresh()->reference);
    }

    public function test_payment_page_regenerates_qr_after_expiry_with_suffix_ref(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay();

        app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        $firstReference = Payment::query()->firstOrFail()->reference;
        $order->update(['qr_expires_at' => now()->subHour()]);

        $component = Livewire::actingAs($user)
            ->test(PaymentPage::class, ['orderId' => $order->id])
            ->assertOk()
            ->assertSee('Kode QR sudah kedaluwarsa')
            ->assertSee('Buat QR baru');

        $component->call('regenerate')
            ->assertSee('Kode QR baru berhasil dibuat.')
            ->assertSee('Kode QR QRIS');

        $payment = Payment::query()->firstOrFail()->fresh();
        $this->assertSame($order->order_number.'-R2', $payment->merchant_ref);
        $this->assertNotSame($firstReference, $payment->reference);
        $this->assertSame('pending', $payment->status);
        $this->assertTrue($order->fresh()->qr_expires_at->isFuture());
    }

    public function test_payment_page_check_status_button_marks_paid(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);
        $this->fakeTripay(['detailStatus' => 'PAID']);

        app(TripayService::class)->createClosedTransaction($order, $order->order_number);

        Livewire::actingAs($user)
            ->test(PaymentPage::class, ['orderId' => $order->id])
            ->assertOk()
            ->call('checkStatus')
            ->assertSee('Pembayaran terverifikasi')
            ->assertSee('Pembayaran sudah terverifikasi');

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_payment_page_rejects_guest_and_other_user(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);

        $this->get('/pesanan/'.$order->id.'/bayar')->assertRedirect('/login');

        $other = User::factory()->create(['role' => 'customer']);

        $this->actingAs($other)
            ->get('/pesanan/'.$order->id.'/bayar')
            ->assertNotFound();
    }

    public function test_order_detail_links_to_payment_page_while_pending(): void
    {
        $user = $this->customer();
        $order = $this->makeOrder($user);

        $this->actingAs($user)
            ->get('/pesanan/'.$order->id)
            ->assertOk()
            ->assertSee('Lihat pembayaran')
            ->assertSee(route('orders.pay', $order));
    }
}
