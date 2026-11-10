<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Payment;
use App\Services\TripayService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Halaman pembayaran QRIS untuk satu order. Transaksi Tripay
 * dibuat secara malas pada pemuatan pertama, hanya bila belum
 * ada referensi dan kredensial terisi; pemuatan ulang memakai
 * ulang referensi yang masih berlaku, dan kode baru hanya
 * dibuat lewat tombol setelah kedaluwarsa. Status di halaman
 * ini dibaca dari database (diperbarui polling ringan), bukan
 * dari pemanggilan Tripay tiap render; Tripay hanya dipanggil
 * lewat tombol "Cek status pembayaran".
 */
class PaymentPage extends Component
{
    public int $orderId;

    public ?string $message = null;

    public ?string $error = null;

    public bool $credentialsMissing = false;

    /** @var list<array{title: string, steps: list<string>}> */
    public array $instructions = [];

    public function mount(int $orderId, TripayService $tripay): void
    {
        $order = Order::query()->findOrFail($orderId);

        // Pemilik atau admin (sama seperti rute orders.pay);
        // admin bisa membuka halaman bayar dari detail admin.
        abort_unless($order->user_id === auth()->id() || auth()->user()?->isAdmin(), 404);

        $this->orderId = $order->id;

        $this->ensureTransaction($tripay);
        $this->loadInstructions($tripay);
    }

    /**
     * Buat kode QR baru setelah yang lama kedaluwarsa, dengan
     * merchant ref percobaan berikutnya (-R2, -R3, ...).
     */
    public function regenerate(TripayService $tripay): void
    {
        $order = $this->freshOrder();
        $payment = $this->paymentFor($order);

        if ($payment === null || $payment->status === 'paid' || ! $tripay->hasCredentials()) {
            return;
        }

        $this->message = null;
        $this->error = null;

        $result = $tripay->createClosedTransaction(
            $order,
            $tripay->nextMerchantRef($order, $payment)
        );

        if ($result['ok']) {
            $this->message = 'Kode QR baru berhasil dibuat. Kode lama tidak berlaku lagi.';
            $this->loadInstructions($tripay);
        } else {
            $this->error = $result['message'];
        }
    }

    /**
     * Coba lagi membuat transaksi dengan merchant ref yang sama,
     * untuk kasus pembuatan pertama yang gagal (misalnya
     * Tripay menolak atau tidak terhubung) dan belum ada
     * referensi sama sekali.
     */
    public function retryCreate(TripayService $tripay): void
    {
        $order = $this->freshOrder();
        $payment = $this->paymentFor($order);

        if ($payment === null || $payment->reference !== null || $payment->status === 'paid') {
            return;
        }

        $this->ensureTransaction($tripay);
        $this->loadInstructions($tripay);
    }

    /**
     * Cek status manual ke Tripay dan terapkan transisi yang
     * sama seperti callback, secara idempoten.
     */
    public function checkStatus(TripayService $tripay): void
    {
        $order = $this->freshOrder();
        $payment = $this->paymentFor($order);

        if ($payment === null) {
            return;
        }

        $this->message = null;
        $this->error = null;

        $result = $tripay->checkStatus($payment);

        if ($result['ok']) {
            $this->message = $result['message'];
        } else {
            $this->error = $result['message'];
        }
    }

    /**
     * Dipanggil polling halaman saat masih menunggu: render
     * ulang sudah memuat ulang order dan payment dari database.
     */
    public function refreshStatus(): void
    {
        //
    }

    public function render(): View
    {
        $order = $this->freshOrder();
        $payment = $this->paymentFor($order);

        $qrString = null;
        $expiresAt = $order->qr_expires_at;
        $merchantFee = null;

        if ($payment !== null && is_array($payment->payload)) {
            $qrString = $payment->payload['data']['qr_string'] ?? null;

            // Biaya merchant hanya ditampilkan bila benar-benar
            // ada di payload Tripay yang tersimpan; bila tidak
            // ada datanya, baris biaya tidak ditampilkan sama
            // sekali dan angkanya tidak dikarang.
            $fee = $payment->payload['data']['fee_merchant'] ?? null;

            if (is_numeric($fee)) {
                $merchantFee = (int) $fee;
            }

            if ($expiresAt === null && isset($payment->payload['data']['expired_time'])) {
                $expiresAt = Carbon::createFromTimestamp((int) $payment->payload['data']['expired_time'], config('app.timezone'));
            }
        }

        $isPaid = $order->payment_status === 'paid' || ($payment !== null && $payment->status === 'paid');
        $isExpired = ! $isPaid
            && $payment !== null
            && $payment->reference !== null
            && $expiresAt !== null
            && now()->greaterThan($expiresAt);

        return view('livewire.payment-page', [
            'order' => $order,
            'payment' => $payment,
            'qrString' => is_string($qrString) && $qrString !== '' ? $qrString : null,
            'expiresAt' => $expiresAt,
            'merchantFee' => $merchantFee,
            'isPaid' => $isPaid,
            'isExpired' => $isExpired,
            'showPoll' => ! $isPaid && ! $isExpired && $payment !== null && $payment->reference !== null && $payment->status === 'pending',
        ]);
    }

    private function freshOrder(): Order
    {
        return Order::query()
            ->with(['items', 'payments'])
            ->findOrFail($this->orderId);
    }

    private function paymentFor(Order $order): ?Payment
    {
        return $order->payments->sortBy('id')->first();
    }

    private function ensureTransaction(TripayService $tripay): void
    {
        $order = $this->freshOrder();
        $payment = $this->paymentFor($order);

        // Order yang sudah dibatalkan (manual atau karena
        // kedaluwarsa) tidak boleh memicu pembuatan transaksi
        // baru bila halaman bayarnya dibuka lagi.
        if ($order->order_status === 'cancelled') {
            return;
        }

        if ($payment === null || $payment->status === 'paid' || $order->payment_status === 'paid') {
            return;
        }

        if (! $tripay->hasCredentials()) {
            $this->credentialsMissing = true;

            return;
        }

        if ($payment->reference !== null) {
            return;
        }

        $result = $tripay->createClosedTransaction(
            $order,
            (string) ($payment->merchant_ref ?? $order->order_number)
        );

        if (! $result['ok']) {
            $this->error = $result['message'];
        }
    }

    private function loadInstructions(TripayService $tripay): void
    {
        $order = $this->freshOrder();
        $payment = $this->paymentFor($order);

        if ($payment === null || $payment->reference === null || $payment->status === 'paid') {
            $this->instructions = [];

            return;
        }

        $qrString = null;

        if (is_array($payment->payload)) {
            $qrString = $payment->payload['data']['qr_string'] ?? null;
        }

        $this->instructions = $tripay->paymentInstruction(
            $tripay->channel(),
            is_string($qrString) ? $qrString : null,
            $order->total
        );
    }
}
