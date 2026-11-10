<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Riwayat pesanan milik user yang login, terbaru dulu.
     * Filter GET: q (nomor order) dan order_status.
     */
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('order_number', 'like', '%'.trim((string) $request->query('q')).'%');
            })
            ->when($request->filled('order_status'), function ($query) use ($request) {
                $query->where('order_status', $request->query('order_status'));
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        // Hitungan per tab dihitung dari seluruh pesanan milik
        // user, tanpa filter pencarian/status, supaya angka tab
        // selalu konsisten dengan isi daftar.
        $grouped = Order::query()
            ->where('user_id', $request->user()->id)
            ->selectRaw('order_status, COUNT(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status');

        $statusCounts = array_merge(
            ['all' => (int) $grouped->sum()],
            $grouped->map(fn ($total) => (int) $total)->toArray()
        );

        return view('orders.index', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
        ]);
    }

    /**
     * Batalkan pesanan sendiri lewat layanan bersama
     * OrderService::cancelOrder. Bukan pemilik mendapat 404.
     */
    public function cancel(Order $order, OrderService $orders): RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 404);

        $result = $orders->cancelOrder($order);

        return redirect()
            ->route('orders.show', $order)
            ->with($result['ok'] ? 'status' : 'error', $result['message']);
    }
}
