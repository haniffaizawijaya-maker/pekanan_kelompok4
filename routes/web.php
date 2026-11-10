<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TripayCallbackController;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('catalog.index');
})->name('catalog.index');

Route::get('/produk/{product:slug}', function (Product $product) {
    abort_unless($product->is_active, 404);

    return view('products.show', ['product' => $product]);
})->name('products.show');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/tripay/callback', TripayCallbackController::class)->name('tripay.callback');

Route::middleware('auth')->group(function () {
    Route::get('/keranjang', function () {
        return view('cart.index');
    })->name('cart.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// M2: checkout, riwayat, detail, batal, dan halaman bayar
// hanya untuk email yang sudah terverifikasi, karena produk
// digital dikirim ke alamat email pembeli. Keranjang dan
// profil tetap cukup auth saja.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/checkout', function () {
        return view('checkout.index');
    })->name('checkout.index');

    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders.index');

    Route::get('/pesanan/{order}', function (Order $order) {
        abort_unless($order->user_id === auth()->id(), 404);

        // Banner hanya untuk pemuatan detail pertama setelah checkout:
        // butuh penanda sesi dari CheckoutPage DAN query ?created=1.
        // Penanda sesi dihapus pada pemuatan detail pertama yang cocok,
        // jadi muat ulang atau URL yang dibagikan tidak menampilkan
        // banner lagi.
        $bannerOrderId = session('order_created_banner');
        $justCreated = $bannerOrderId !== null && (int) $bannerOrderId === $order->id;

        if ($justCreated) {
            session()->forget('order_created_banner');
        }

        $showBanner = $justCreated && request()->query('created') === '1';

        $order->load(['items.product', 'payments', 'user']);

        return view('orders.show', [
            'order' => $order,
            'showBanner' => $showBanner,
            'canCancel' => $order->isCancellable(),
            'cancelReason' => $order->cancelBlockReason(),
        ]);
    })->name('orders.show');

    Route::post('/pesanan/{order}/batal', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/pesanan/{order}/bayar', function (Order $order) {
        // Pemilik atau admin; PaymentPage juga memeriksa hal
        // yang sama di mount-nya.
        abort_unless($order->user_id === auth()->id() || auth()->user()?->isAdmin(), 404);

        return view('orders.pay', ['order' => $order]);
    })->name('orders.pay');
});

// Panel admin: tamu diarahkan login oleh auth, bukan admin
// mendapat 403 dari middleware admin.
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/pesanan', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/pesanan/{order}/selesai', [AdminOrderController::class, 'complete'])->name('orders.complete');
    Route::post('/pesanan/{order}/batal', [AdminOrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/produk', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/produk/buat', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/produk', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/produk/{product}/ubah', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::match(['put', 'patch'], '/produk/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::delete('/produk/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
    Route::patch('/produk/{product}/aktif', [AdminProductController::class, 'toggle'])->name('products.toggle');
});

require __DIR__.'/auth.php';
