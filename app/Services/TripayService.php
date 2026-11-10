<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Satu-satunya tempat pemanggilan HTTP ke Tripay, seperti
 * CartService untuk keranjang dan OrderService untuk order.
 * Komponen Livewire dan controller callback hanya memanggil
 * service ini; tidak ada pemanggilan API Tripay di tempat lain.
 *
 * Mengikuti API Developer Guide resmi Tripay
 * (https://tripay.co.id/developer): transaksi closed payment
 * lewat POST transaction/create, detail lewat GET
 * transaction/detail, channel lewat GET merchant/payment-channel,
 * instruksi lewat GET payment/instruction, dan callback
 * terverifikasi dari raw JSON body dengan HMAC-SHA256.
 *
 * Channel yang dipakai adalah QRIS2 (QRIS umum). Channel bernama
 * QRIS adalah QRIS by ShopeePay, jadi jangan tertukar.
 *
 * Tidak ada kunci di file ini. Kredensial hanya dari config
 * (yang dibaca dari .env lokal) dan tidak pernah ditulis ke
 * log, payload, atau commit.
 */
class TripayService
{
    public function hasCredentials(): bool
    {
        return config('tripay.merchant_code') !== ''
            && config('tripay.api_key') !== ''
            && config('tripay.private_key') !== '';
    }

    public function channel(): string
    {
        return (string) config('tripay.qris_channel', 'QRIS2');
    }

    /**
     * Signature pembuatan transaksi, sesuai dokumen resmi:
     * HMAC-SHA256 dari gabungan merchantCode + merchant_ref +
     * amount tanpa pemisah, dikunci private key.
     */
    public function createSignature(string $merchantRef, int $amount): string
    {
        return hash_hmac(
            'sha256',
            (string) config('tripay.merchant_code').$merchantRef.$amount,
            (string) config('tripay.private_key')
        );
    }

    /**
     * Signature callback, sesuai dokumen resmi: HMAC-SHA256 dari
     * raw JSON body persis seperti diterima, dikunci private key.
     * Body tidak boleh di-parsing lalu di-encode ulang untuk ini.
     */
    public function callbackSignature(string $rawJson): string
    {
        return hash_hmac('sha256', $rawJson, (string) config('tripay.private_key'));
    }

    /**
     * Verifikasi signature callback dengan perbandingan waktu
     * konstan. Tanpa private key yang terisi, tidak ada signature
     * yang bisa dianggap sah.
     */
    public function verifyCallbackSignature(?string $incoming, string $rawJson): bool
    {
        if (config('tripay.private_key') === '' || $incoming === null || $incoming === '') {
            return false;
        }

        $expected = $this->callbackSignature($rawJson);

        if (strlen($incoming) !== strlen($expected)) {
            return false;
        }

        return hash_equals($expected, $incoming);
    }

    /**
     * Buat transaksi closed payment QRIS2 untuk satu order.
     * Merchant ref pertama adalah nomor order; percobaan ulang
     * setelah kedaluwarsa memakai suffix -R2, -R3, dan seterusnya
     * (lihat nextMerchantRef). Payment yang sama diperbarui di
     * tempatnya: tidak dibuat baris Payment baru tiap percobaan.
     *
     * M3: pembuatan dibungkus kunci cache per order (TTL 30
     * detik, tunggu sampai 10 detik) agar dua pemuatan halaman
     * bayar bersamaan tidak membuat dua transaksi Tripay.
     * Setelah kunci didapat, referensi dicek ulang: bila sudah
     * ada untuk merchant ref yang sama, transaksi yang sudah
     * ada dikembalikan tanpa pemanggilan HTTP baru. Kunci
     * dilepas otomatis oleh block() di finally internalnya,
     * dan TTL membatasi penahanan bila proses mati di tengah.
     * Bila kunci tidak didapat, kembalikan error jujur tanpa
     * crash; pembayaran tetap menunggu dan bisa dicoba lagi.
     *
     * @return array{ok: bool, message: string, payment: ?Payment}
     */
    public function createClosedTransaction(Order $order, string $merchantRef, int $lockWaitSeconds = 10): array
    {
        $lock = Cache::lock('tripay-create-order-'.$order->id, 30);

        try {
            return $lock->block($lockWaitSeconds, function () use ($order, $merchantRef) {
                return $this->createClosedTransactionLocked($order, $merchantRef);
            });
        } catch (LockTimeoutException) {
            Log::warning('Tripay create ditunda: kunci pembuatan sedang dipegang proses lain.', ['order_id' => $order->id]);

            return [
                'ok' => false,
                'message' => 'Pembuatan kode QR sedang diproses. Tunggu sebentar lalu coba lagi.',
                'payment' => $order->payments()->orderBy('id')->first(),
            ];
        }
    }

    /**
     * Isi pembuatan transaksi, hanya boleh dipanggil setelah
     * kunci cache per order didapat (lihat
     * createClosedTransaction). Membaca ulang Payment dari
     * database agar pemuatan kedua yang menunggu kunci tidak
     * membuat transaksi duplikat.
     */
    private function createClosedTransactionLocked(Order $order, string $merchantRef): array
    {
        if (! $this->hasCredentials()) {
            return [
                'ok' => false,
                'message' => 'Kredensial Tripay belum diisi.',
                'payment' => null,
            ];
        }

        $order->loadMissing(['items', 'user']);

        $payment = $order->payments()->orderBy('id')->first();

        if ($payment === null) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'gateway' => 'tripay',
                'reference' => null,
                'merchant_ref' => $order->order_number,
                'qr_url' => null,
                'amount' => $order->total,
                'status' => 'pending',
                'paid_at' => null,
                'payload' => null,
            ]);
        }

        if ($payment->status === 'paid') {
            return [
                'ok' => false,
                'message' => 'Pesanan ini sudah dibayar.',
                'payment' => $payment,
            ];
        }

        // Pemuatan lain yang menunggu kunci dan mendapati
        // referensi sudah ada untuk merchant ref yang sama
        // memakai transaksi itu, tanpa HTTP baru. Merchant ref
        // yang berbeda berarti percobaan ulang setelah
        // kedaluwarsa (regenerasi), jadi pembuatan berlanjut.
        if ($payment->reference !== null && $payment->merchant_ref === $merchantRef) {
            return [
                'ok' => true,
                'message' => 'Kode QR sudah dibuat.',
                'payment' => $payment->fresh(),
            ];
        }

        $expiredAt = now()->addHours($this->expiredHours())->timestamp;

        $payload = [
            'method' => $this->channel(),
            'merchant_ref' => $merchantRef,
            'amount' => $order->total,
            'customer_name' => $order->buyer_name ?? $order->user?->name ?? 'Pelanggan',
            'customer_email' => $order->buyer_email ?? $order->user?->email ?? '',
            'order_items' => $order->items->map(fn ($item) => [
                'sku' => 'P'.($item->product_id ?? 0),
                'name' => $item->product_name,
                'price' => $item->price_at_checkout,
                'quantity' => $item->quantity,
            ])->all(),
            'callback_url' => route('tripay.callback'),
            'return_url' => route('orders.show', $order),
            'expired_time' => $expiredAt,
            'signature' => $this->createSignature($merchantRef, $order->total),
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('tripay.api_key'),
            ])
                ->timeout(20)
                ->asForm()
                ->post($this->baseUrl().'transaction/create', $payload);
        } catch (ConnectionException $e) {
            Log::warning('Tripay create gagal: tidak dapat terhubung.', ['order_id' => $order->id]);

            return [
                'ok' => false,
                'message' => 'Tripay tidak dapat dihubungi. Coba lagi sebentar lagi.',
                'payment' => $payment,
            ];
        }

        $body = $response->json();

        if (! is_array($body) || ($body['success'] ?? false) !== true || empty($body['data']['reference'])) {
            Log::warning('Tripay create gagal.', [
                'order_id' => $order->id,
                'message' => is_array($body) ? ($body['message'] ?? null) : null,
            ]);

            return [
                'ok' => false,
                'message' => 'Tripay menolak pembuatan pembayaran. Periksa channel QRIS2 sudah aktif di akun merchant, lalu coba lagi.',
                'payment' => $payment,
            ];
        }

        $data = $body['data'];
        $expiresAt = isset($data['expired_time']) ? Carbon::createFromTimestamp((int) $data['expired_time'], config('app.timezone')) : null;

        $payment->update([
            'merchant_ref' => $merchantRef,
            'reference' => $data['reference'],
            'qr_url' => $data['qr_url'] ?? null,
            'status' => 'pending',
            'payload' => $body,
        ]);

        // Biaya Tripay (fee_merchant, total_fee, amount_received)
        // tidak punya kolom sendiri; semuanya tersimpan di payload
        // JSON respons mentah di atas.
        $order->update([
            'gateway_reference' => $data['reference'],
            'tripay_reference' => $data['reference'],
            'qr_expires_at' => $expiresAt,
        ]);

        return [
            'ok' => true,
            'message' => 'Kode QR berhasil dibuat.',
            'payment' => $payment->fresh(),
        ];
    }

    /**
     * Detail transaksi dari Tripay, dipakai untuk tombol
     * "Cek status pembayaran" sebagai cadangan bila callback
     * belum sampai.
     *
     * @return array{ok: bool, message: string, data: ?array}
     */
    public function transactionDetail(string $reference): array
    {
        if (! $this->hasCredentials()) {
            return ['ok' => false, 'message' => 'Kredensial Tripay belum diisi.', 'data' => null];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('tripay.api_key'),
            ])
                ->timeout(20)
                ->get($this->baseUrl().'transaction/detail', ['reference' => $reference]);
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Tripay tidak dapat dihubungi. Coba lagi sebentar lagi.', 'data' => null];
        }

        $body = $response->json();

        if (! is_array($body) || ($body['success'] ?? false) !== true || ! is_array($body['data'] ?? null)) {
            return [
                'ok' => false,
                'message' => 'Status pembayaran tidak dapat dibaca dari Tripay.',
                'data' => null,
            ];
        }

        return ['ok' => true, 'message' => '', 'data' => $body['data']];
    }

    /**
     * Cek status manual: ambil detail dari Tripay lalu terapkan
     * transisi yang sama seperti callback, secara idempoten.
     *
     * @return array{ok: bool, outcome: string, message: string}
     */
    public function checkStatus(Payment $payment): array
    {
        if ($payment->reference === null) {
            return [
                'ok' => false,
                'outcome' => 'no_reference',
                'message' => 'Belum ada kode pembayaran. Buat kode QR dulu.',
            ];
        }

        $detail = $this->transactionDetail($payment->reference);

        if (! $detail['ok'] || $detail['data'] === null) {
            return ['ok' => false, 'outcome' => 'error', 'message' => $detail['message']];
        }

        $result = $this->applyTransactionStatus(
            $payment,
            self::normalizeDetail($detail['data']),
            $detail['data'],
            'detail'
        );

        return [
            'ok' => true,
            'outcome' => $result['outcome'],
            'message' => $result['message'],
        ];
    }

    /**
     * Daftar channel pembayaran aktif pada akun merchant.
     * Mengembalikan list mentah dari respons Tripay, atau list
     * kosong bila gagal; pemanggil tidak boleh gagal karenanya.
     *
     * @return list<array>
     */
    public function merchantPaymentChannels(): array
    {
        if (! $this->hasCredentials()) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('tripay.api_key'),
            ])
                ->timeout(20)
                ->get($this->baseUrl().'merchant/payment-channel');
        } catch (ConnectionException) {
            return [];
        }

        $body = $response->json();

        if (! is_array($body) || ($body['success'] ?? false) !== true || ! is_array($body['data'] ?? null)) {
            return [];
        }

        return $body['data'];
    }

    /**
     * Apakah channel QRIS2 yang dikonfigurasi aktif di akun
     * merchant (entri berkode sama dengan flag active true).
     */
    public function isQrisChannelAvailable(): bool
    {
        foreach ($this->merchantPaymentChannels() as $channel) {
            if (($channel['code'] ?? null) === $this->channel() && ($channel['active'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Langkah pembayaran dari Tripay untuk satu channel. Gagal
     * mengambil instruksi bukan kegagalan halaman: pemanggil
     * memakai langkah generik. Langkah dibersihkan dari tag HTML
     * apa pun sebelum ditampilkan.
     *
     * @return list<array{title: string, steps: list<string>}>
     */
    public function paymentInstruction(string $code, ?string $payCode, ?int $amount): array
    {
        if (! $this->hasCredentials()) {
            return [];
        }

        $query = ['code' => $code, 'allow_html' => 0];

        if ($payCode !== null && $payCode !== '') {
            $query['pay_code'] = $payCode;
        }

        if ($amount !== null) {
            $query['amount'] = $amount;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('tripay.api_key'),
            ])
                ->timeout(20)
                ->get($this->baseUrl().'payment/instruction', $query);
        } catch (ConnectionException) {
            return [];
        }

        $body = $response->json();

        if (! is_array($body) || ($body['success'] ?? false) !== true || ! is_array($body['data'] ?? null)) {
            return [];
        }

        $instructions = [];

        foreach ($body['data'] as $group) {
            if (! is_array($group)) {
                continue;
            }

            $steps = [];

            foreach ((array) ($group['steps'] ?? []) as $step) {
                $plain = trim(strip_tags((string) $step));

                if ($plain !== '') {
                    $steps[] = $plain;
                }
            }

            if ($steps !== []) {
                $instructions[] = [
                    'title' => trim(strip_tags((string) ($group['title'] ?? 'Cara membayar'))),
                    'steps' => $steps,
                ];
            }
        }

        return $instructions;
    }

    /**
     * Merchant ref untuk percobaan berikutnya: percobaan pertama
     * memakai nomor order apa adanya, percobaan berikutnya
     * memakai suffix -R2, -R3, dan seterusnya.
     */
    public function nextMerchantRef(Order $order, Payment $payment): string
    {
        $current = (string) ($payment->merchant_ref ?? $order->order_number);

        if (preg_match('/-R(\d+)$/', $current, $matches) === 1) {
            return $order->order_number.'-R'.((int) $matches[1] + 1);
        }

        return $order->order_number.'-R2';
    }

    /**
     * Normalisasi data callback Tripay ke bentuk umum.
     * Field callback: reference, merchant_ref, total_amount,
     * status, paid_at (unix timestamp).
     *
     * @return array{reference: ?string, merchant_ref: ?string, status: string, amount: ?int, paid_at: ?int, expired_time: ?int}
     */
    public static function normalizeCallback(array $data): array
    {
        return [
            'reference' => isset($data['reference']) ? (string) $data['reference'] : null,
            'merchant_ref' => isset($data['merchant_ref']) ? (string) $data['merchant_ref'] : null,
            'status' => strtoupper((string) ($data['status'] ?? '')),
            'amount' => isset($data['total_amount']) ? (int) $data['total_amount'] : null,
            'paid_at' => isset($data['paid_at']) ? (int) $data['paid_at'] : null,
            'expired_time' => null,
        ];
    }

    /**
     * Normalisasi data detail transaksi Tripay ke bentuk umum.
     * Field detail: reference, merchant_ref, amount, status,
     * paid_at (unix timestamp dalam string), expired_time.
     *
     * @return array{reference: ?string, merchant_ref: ?string, status: string, amount: ?int, paid_at: ?int, expired_time: ?int}
     */
    public static function normalizeDetail(array $data): array
    {
        return [
            'reference' => isset($data['reference']) ? (string) $data['reference'] : null,
            'merchant_ref' => isset($data['merchant_ref']) ? (string) $data['merchant_ref'] : null,
            'status' => strtoupper((string) ($data['status'] ?? '')),
            'amount' => isset($data['amount']) ? (int) $data['amount'] : null,
            'paid_at' => isset($data['paid_at']) && $data['paid_at'] !== '' ? (int) $data['paid_at'] : null,
            'expired_time' => isset($data['expired_time']) ? (int) $data['expired_time'] : null,
        ];
    }

    /**
     * Terapkan satu status Tripay ke Payment dan Order, secara
     * idempoten dan di dalam satu transaksi. Hanya Payment yang
     * masih menunggu yang boleh berubah status; Payment yang
     * sudah lunas, kedaluwarsa, atau gagal hanya dicatat
     * payload-nya. Stok hanya dikembalikan SEKALI, pada transisi
     * dari menunggu ke kedaluwarsa/gagal, karena guard status
     * membuat callback atau cek status duplikat tidak masuk ke
     * cabang ini lagi.
     *
     * Pemetaan memakai nilai status yang sudah ada di migrasi:
     * PAID menjadi payment paid + order paid/paid; EXPIRED dan
     * FAILED menjadi payment expired/failed + order cancelled
     * dan payment_status failed; REFUND (M5) menjadi payment
     * refunded tanpa mengubah status order sama sekali dan
     * tanpa mengubah stok, karena pengembalian dana harus
     * direkonsiliasi manual di dashboard Tripay; status lain
     * (UNPAID dan yang tidak dikenal) hanya menyimpan payload.
     *
     * @param  array{reference: ?string, merchant_ref: ?string, status: string, amount: ?int, paid_at: ?int, expired_time: ?int}  $normalized
     * @return array{outcome: string, message: string}
     */
    public function applyTransactionStatus(Payment $payment, array $normalized, array $raw, string $source): array
    {
        return DB::transaction(function () use ($payment, $normalized, $raw, $source) {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = Order::query()->lockForUpdate()->findOrFail($locked->order_id);

            $payload = is_array($locked->payload) ? $locked->payload : [];
            $payload[$source] = $raw;
            $payload[$source.'_received_at'] = now()->toDateTimeString();

            $locked->payload = $payload;
            $locked->save();

            // Payment yang sudah final tidak berubah, kecuali
            // REFUND untuk payment yang sudah dibayar: dana yang
            // dikembalikan harus tercatat di payment, walau
            // status order tidak berubah (lihat cabang REFUND).
            if ($locked->status !== 'pending' && ! ($normalized['status'] === 'REFUND' && $locked->status === 'paid')) {
                return [
                    'outcome' => 'already_settled',
                    'message' => 'Status pembayaran sudah final dan tidak berubah.',
                ];
            }

            if ($normalized['amount'] !== null
                && ($normalized['amount'] !== $locked->amount || $normalized['amount'] !== $order->total)
            ) {
                Log::warning('Tripay status diabaikan: nominal tidak cocok.', [
                    'order_id' => $order->id,
                    'payment_id' => $locked->id,
                ]);

                return [
                    'outcome' => 'amount_mismatch',
                    'message' => 'Nominal dari Tripay tidak cocok dengan pesanan, jadi status tidak diubah.',
                ];
            }

            if ($locked->reference !== null
                && $normalized['reference'] !== null
                && $normalized['reference'] !== $locked->reference
            ) {
                Log::warning('Tripay status diabaikan: referensi tidak cocok.', [
                    'order_id' => $order->id,
                    'payment_id' => $locked->id,
                ]);

                return [
                    'outcome' => 'reference_mismatch',
                    'message' => 'Referensi dari Tripay tidak cocok dengan pembayaran ini, jadi status tidak diubah.',
                ];
            }

            $status = $normalized['status'];
            $paidAt = $normalized['paid_at'] !== null
                ? Carbon::createFromTimestamp($normalized['paid_at'], config('app.timezone'))
                : now();

            // M5: REFUND hanya mengubah status payment menjadi
            // refunded. Status order, payment_status order, dan
            // stok tidak diubah; pengembalian dana direkonsiliasi
            // manual di dashboard Tripay. Guard nominal dan
            // referensi di atas tetap berlaku untuk REFUND.
            if ($status === 'REFUND' && in_array($locked->status, ['pending', 'paid'], true)) {
                $locked->update([
                    'status' => 'refunded',
                    'payload' => $payload,
                ]);

                return [
                    'outcome' => 'refunded',
                    'message' => 'Pembayaran telah dikembalikan. Status pesanan tidak berubah; rekonsiliasi dilakukan manual di dashboard Tripay.',
                ];
            }

            if ($status === 'PAID') {
                $locked->update([
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                    'payload' => $payload,
                ]);

                $order->update([
                    'order_status' => 'paid',
                    'payment_status' => 'paid',
                    'gateway_reference' => $normalized['reference'] ?? $locked->reference,
                    'tripay_reference' => $normalized['reference'] ?? $locked->reference,
                    'paid_at' => $paidAt,
                ]);

                return ['outcome' => 'paid', 'message' => 'Pembayaran terverifikasi. Pesanan sudah dibayar.'];
            }

            if ($status === 'EXPIRED' || $status === 'FAILED') {
                self::restockOrderItems($order);

                $locked->update([
                    'status' => $status === 'EXPIRED' ? 'expired' : 'failed',
                    'payload' => $payload,
                ]);

                $order->update([
                    'order_status' => 'cancelled',
                    'payment_status' => 'failed',
                ]);

                return [
                    'outcome' => $status === 'EXPIRED' ? 'expired' : 'failed',
                    'message' => $status === 'EXPIRED'
                        ? 'Kode pembayaran sudah kedaluwarsa. Stok dikembalikan dan pesanan dibatalkan.'
                        : 'Pembayaran gagal. Stok dikembalikan dan pesanan dibatalkan.',
                ];
            }

            if ($normalized['expired_time'] !== null) {
                $order->update([
                    'qr_expires_at' => Carbon::createFromTimestamp($normalized['expired_time'], config('app.timezone')),
                ]);
            }

            return [
                'outcome' => 'ignored',
                'message' => 'Status pembayaran masih menunggu.',
            ];
        });
    }

    /**
     * Kembalikan stok setiap item order. Hanya dipanggil dari
     * dalam transisi menunggu ke kedaluwarsa/gagal (Tripay)
     * atau ke batal manual (OrderService::cancelOrder), yang
     * keduanya mengunci order dan payment dan memastikan
     * status masih menunggu sebelum memanggil ini, sehingga
     * tidak mungkin berjalan dua kali untuk satu Payment.
     * Dibuat public static agar dipakai ulang oleh
     * OrderService tanpa duplikasi guard; perilakunya tidak
     * berubah.
     */
    public static function restockOrderItems(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $product = Product::query()->lockForUpdate()->find($item->product_id);

            if ($product !== null) {
                $product->increment('stock', $item->quantity);
            }
        }
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('tripay.base_url', 'https://tripay.co.id/api-sandbox/'), '/').'/';
    }

    /**
     * Batas kedaluwarsa QRIS2 menurut dokumen resmi adalah 10
     * menit sampai 1440 menit (24 jam); konfigurasi dijepit ke
     * rentang 1 sampai 24 jam.
     */
    private function expiredHours(): int
    {
        return max(1, min(24, (int) config('tripay.expired_hours', 24)));
    }
}
