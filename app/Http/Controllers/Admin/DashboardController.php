<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Ringkasan admin dari data yang sudah ada, tanpa library
     * chart. Pendapatan hanya menghitung order yang sudah
     * dibayar atau selesai.
     */
    public function index(): View
    {
        $statuses = ['pending', 'paid', 'completed', 'cancelled'];

        $counts = Order::query()
            ->selectRaw('order_status, count(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status')
            ->all();

        $revenue = Order::query()
            ->whereIn('order_status', ['paid', 'completed'])
            ->sum('total');

        $lowStock = Product::query()
            ->with('category')
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(10)
            ->get();

        $latestOrders = Order::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('admin.dashboard', [
            'revenue' => (int) $revenue,
            'orderCounts' => array_map(
                fn (string $status): int => (int) ($counts[$status] ?? 0),
                array_combine($statuses, $statuses)
            ),
            'totalOrders' => (int) array_sum($counts),
            'totalProducts' => Product::query()->count(),
            'activeProducts' => Product::query()->where('is_active', true)->count(),
            'lowStock' => $lowStock,
            'latestOrders' => $latestOrders,
        ]);
    }
}
