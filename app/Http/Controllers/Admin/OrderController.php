<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Semua pesanan. Filter GET: q (nomor order, nama, atau
     * email pembeli), order_status, dan payment_status.
     */
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with('user')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.trim((string) $request->query('q')).'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('order_number', 'like', $term)
                        ->orWhere('buyer_name', 'like', $term)
                        ->orWhere('buyer_email', 'like', $term);
                });
            })
            ->when($request->filled('order_status'), function ($query) use ($request) {
                $query->where('order_status', $request->query('order_status'));
            })
            ->when($request->filled('payment_status'), function ($query) use ($request) {
                $query->where('payment_status', $request->query('payment_status'));
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        // Hitungan per tab dari seluruh pesanan, tanpa filter
        // pencarian/status, supaya angka tab konsisten.
        $grouped = Order::query()
            ->selectRaw('order_status, COUNT(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status');

        $statusCounts = array_merge(
            ['all' => (int) $grouped->sum()],
            $grouped->map(fn ($total) => (int) $total)->toArray()
        );

        return view('admin.orders.index', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'payments', 'user']);

        return view('admin.orders.show', [
            'order' => $order,
            'canCancel' => $order->isCancellable(),
            'cancelReason' => $order->cancelBlockReason(),
        ]);
    }

    /**
     * Tandai selesai, hanya dari status paid. Transisi tidak
     * valid kembali dengan pesan error tanpa perubahan state.
     */
    public function complete(Order $order, OrderService $orders): RedirectResponse
    {
        $result = $orders->markCompleted($order);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with($result['ok'] ? 'status' : 'error', $result['message']);
    }

    /**
     * Batalkan pesanan lewat layanan bersama yang sama dengan
     * pembatalan customer.
     */
    public function cancel(Order $order, OrderService $orders): RedirectResponse
    {
        $result = $orders->cancelOrder($order);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with($result['ok'] ? 'status' : 'error', $result['message']);
    }
}
