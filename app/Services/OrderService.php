<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Satu-satunya tempat logika pembuatan order, seperti CartService
 * untuk keranjang. Komponen Livewire hanya memanggil service ini.
 *
 * Produk digital: tidak ada alamat pengiriman dan ongkos kirim
 * selalu Rp0. Harga yang dipakai adalah harga produk terbaru saat
 * checkout (bukan harga yang tersimpan di cart_items), dan stok
 * dikunci serta dikurangi di dalam satu transaksi.
 */
class OrderService
{
    /**
     * Ringkasan keranjang untuk ditampilkan di halaman checkout.
     * Hanya membaca: harga dihitung ulang dari harga produk terbaru,
     * jumlah dipotong ke stok terbaru, item yang sudah tidak tersedia
     * tidak ditampilkan. Keranjang tidak diubah di sini.
     *
     * @return array{lines: list<array{cart_item_id: int, product_id: int, product_name: string, category_name: string, quantity: int, price: int, subtotal: int}>, subtotal: int, notices: list<string>}
     */
    public function preview(User $user): array
    {
        $cart = Cart::query()->where('user_id', $user->id)->first();

        if ($cart === null) {
            return ['lines' => [], 'subtotal' => 0, 'notices' => []];
        }

        $items = CartItem::query()
            ->with('product.category')
            ->where('cart_id', $cart->id)
            ->orderBy('created_at')
            ->get();

        $lines = [];
        $subtotal = 0;
        $notices = [];

        foreach ($items as $item) {
            $product = $item->product;

            if ($product === null || ! $product->is_active || $product->stock <= 0) {
                $notices[] = ($product?->name ?? 'Produk').' tidak lagi tersedia dan tidak dihitung di checkout.';
                continue;
            }

            $quantity = min($item->quantity, $product->stock);

            if ($quantity < $item->quantity) {
                $notices[] = "Jumlah {$product->name} disesuaikan menjadi {$quantity} sesuai stok yang tersedia.";
            }

            $lineSubtotal = $product->price * $quantity;
            $subtotal += $lineSubtotal;

            $lines[] = [
                'cart_item_id' => $item->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'category_name' => $product->category?->name ?? '',
                'quantity' => $quantity,
                'price' => $product->price,
                'subtotal' => $lineSubtotal,
            ];
        }

        return ['lines' => $lines, 'subtotal' => $subtotal, 'notices' => $notices];
    }

    /**
     * Buat order dari keranjang user. Semua validasi ulang, penguncian
     * stok, pembuatan order, pengurangan stok, dan pengosongan keranjang
     * berjalan di dalam satu transaksi.
     *
     * @return array{ok: bool, order: ?Order, notices: list<string>, message: string}
     */
    public function createOrder(User $user, string $buyerName, string $buyerEmail, ?string $notes): array
    {
        return DB::transaction(function () use ($user, $buyerName, $buyerEmail, $notes) {
            $cart = Cart::query()->where('user_id', $user->id)->first();

            if ($cart === null) {
                return $this->failure(
                    [],
                    'Keranjang masih kosong. Tambahkan produk dulu sebelum checkout.'
                );
            }

            $items = CartItem::query()
                ->where('cart_id', $cart->id)
                ->orderBy('created_at')
                ->get();

            if ($items->isEmpty()) {
                return $this->failure(
                    [],
                    'Keranjang masih kosong. Tambahkan produk dulu sebelum checkout.'
                );
            }

            $products = Product::query()
                ->whereIn('id', $items->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = [];
            $notices = [];
            $subtotal = 0;

            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                $name = $product?->name ?? $item->product?->name ?? 'Produk';

                if ($product === null || ! $product->is_active || $product->stock <= 0) {
                    $notices[] = "{$name} dihapus dari keranjang karena sudah tidak tersedia.";
                    $item->delete();
                    continue;
                }

                $quantity = min($item->quantity, $product->stock);

                if ($quantity < $item->quantity) {
                    $notices[] = "Jumlah {$name} disesuaikan menjadi {$quantity} sesuai stok yang tersedia.";
                }

                $lineSubtotal = $product->price * $quantity;
                $subtotal += $lineSubtotal;

                $lines[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'subtotal' => $lineSubtotal,
                ];
            }

            if ($lines === []) {
                $cart->delete();

                return $this->failure(
                    $notices,
                    'Pesanan tidak dapat dibuat karena keranjang menjadi kosong.'
                );
            }

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'buyer_name' => $buyerName,
                'buyer_email' => $buyerEmail,
                'notes' => $notes,
                'address_id' => null,
                'subtotal' => $subtotal,
                'shipping_cost' => 0,
                'total' => $subtotal,
                'order_status' => 'pending',
                'payment_status' => 'unpaid',
                'gateway' => 'tripay',
                'gateway_reference' => null,
                'tripay_reference' => null,
                'qr_expires_at' => null,
                'paid_at' => null,
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price_at_checkout' => $line['price'],
                    'quantity' => $line['quantity'],
                    'subtotal' => $line['subtotal'],
                ]);

                $product->decrement('stock', $line['quantity']);
            }

            // Placeholder pembayaran untuk Fase 4. Belum ada pemanggilan
            // HTTP ke Tripay di sini: tidak ada referensi gateway, QR,
            // atau waktu kedaluwarsa sampai transaksi QRIS dibuat.
            Payment::create([
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

            CartItem::query()->where('cart_id', $cart->id)->delete();
            $cart->delete();

            return [
                'ok' => true,
                'order' => $order,
                'notices' => $notices,
                'message' => 'Pesanan berhasil dibuat.',
            ];
        });
    }

    /**
     * Batalkan satu order. Satu-satunya layanan pembatalan,
     * dipakai customer dan admin. Hanya order yang masih
     * menunggu dengan pembayaran yang masih menunggu yang
     * bisa dibatalkan, dan hanya bila tidak ada kode QR yang
     * masih aktif (belum ada referensi Tripay, atau waktu
     * kedaluwarsa QR sudah lewat).
     *
     * Stok dikembalikan TEPAT SEKALI memakai guard transisi
     * yang sama dengan TripayService: pengembalian hanya
     * terjadi di dalam transaksi ini, setelah order dan
     * payment dikunci dan dipastikan masih menunggu. Karena
     * status payment diubah menjadi cancelled (bukan pending),
     * callback PAID Tripay yang datang belakangan diabaikan
     * oleh guard di TripayService dan tidak bisa menghidupkan
     * order lagi.
     *
     * Pemetaan status memakai nilai yang sudah ada: order
     * menjadi cancelled dengan payment_status failed (sama
     * seperti jalur kedaluwarsa Tripay), dan payment menjadi
     * cancelled dengan label "Dibatalkan".
     *
     * @return array{ok: bool, order: Order, message: string}
     */
    public function cancelOrder(Order $order): array
    {
        return DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $payment = $lockedOrder->payments()->orderBy('id')->lockForUpdate()->first();

            if ($lockedOrder->order_status !== 'pending') {
                return [
                    'ok' => false,
                    'order' => $lockedOrder,
                    'message' => 'Pesanan ini sudah tidak bisa dibatalkan karena statusnya '.$lockedOrder->orderStatusLabel().'.',
                ];
            }

            if ($payment !== null && $payment->status !== 'pending') {
                return [
                    'ok' => false,
                    'order' => $lockedOrder,
                    'message' => 'Pesanan ini sudah tidak bisa dibatalkan karena pembayarannya sudah '.$payment->statusLabel().'.',
                ];
            }

            if ($payment !== null && $payment->reference !== null) {
                $qrExpired = $lockedOrder->qr_expires_at !== null
                    && now()->greaterThanOrEqualTo($lockedOrder->qr_expires_at);

                if (! $qrExpired) {
                    return [
                        'ok' => false,
                        'order' => $lockedOrder,
                        'message' => 'Pesanan ini belum bisa dibatalkan karena kode QR pembayarannya masih aktif. Pembatalan baru bisa dilakukan setelah kode QR kedaluwarsa.',
                    ];
                }
            }

            TripayService::restockOrderItems($lockedOrder);

            if ($payment !== null) {
                $payment->update(['status' => 'cancelled']);
            }

            $lockedOrder->update([
                'order_status' => 'cancelled',
                'payment_status' => 'failed',
            ]);

            return [
                'ok' => true,
                'order' => $lockedOrder->fresh(),
                'message' => 'Pesanan berhasil dibatalkan dan stok dikembalikan.',
            ];
        });
    }

    /**
     * Tandai order yang sudah dibayar sebagai selesai. Hanya
     * dari status paid; transisi lain ditolak tanpa perubahan.
     *
     * @return array{ok: bool, order: Order, message: string}
     */
    public function markCompleted(Order $order): array
    {
        return DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->order_status !== 'paid') {
                return [
                    'ok' => false,
                    'order' => $lockedOrder,
                    'message' => 'Hanya pesanan yang sudah dibayar yang bisa ditandai selesai. Status pesanan ini '.$lockedOrder->orderStatusLabel().'.',
                ];
            }

            $lockedOrder->update(['order_status' => 'completed']);

            return [
                'ok' => true,
                'order' => $lockedOrder->fresh(),
                'message' => 'Pesanan ditandai selesai.',
            ];
        });
    }

    /**
     * @param  list<string>  $notices
     * @return array{ok: bool, order: null, notices: list<string>, message: string}
     */
    private function failure(array $notices, string $message): array
    {
        $full = $notices === []
            ? $message
            : implode(' ', $notices).' '.$message;

        return [
            'ok' => false,
            'order' => null,
            'notices' => $notices,
            'message' => $full,
        ];
    }

    private function generateOrderNumber(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $number = 'MC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

            if (! Order::query()->where('order_number', $number)->exists()) {
                return $number;
            }
        }

        return 'MC-'.now()->format('Ymd').'-'.Str::upper(Str::random(10));
    }
}
