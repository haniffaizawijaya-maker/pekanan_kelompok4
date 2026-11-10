<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold leading-8 text-ink">Panel admin</h1>

        @include('partials.flash')

        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4" aria-label="Total pendapatan">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Total pendapatan</p>
                <p class="mt-1 text-2xl font-bold text-ink">Rp{{ number_format($revenue, 0, ',', '.') }}</p>
                <p class="mt-1 text-sm text-muted">Order dibayar dan selesai.</p>
            </section>
            <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4" aria-label="Jumlah pesanan">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Jumlah pesanan</p>
                <p class="mt-1 text-2xl font-bold text-ink">{{ $totalOrders }}</p>
                <p class="mt-1 text-sm text-muted">Semua status.</p>
            </section>
            <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4" aria-label="Jumlah produk">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Produk</p>
                <p class="mt-1 text-2xl font-bold text-ink">{{ $totalProducts }}</p>
                <p class="mt-1 text-sm text-muted">{{ $activeProducts }} aktif di katalog.</p>
            </section>
            <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4" aria-label="Pesanan per status">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Pesanan per status</p>
                <ul class="mt-2 space-y-1 text-sm leading-6 text-ink">
                    <li class="flex justify-between gap-4"><span>Menunggu pembayaran</span><span class="font-bold">{{ $orderCounts['pending'] }}</span></li>
                    <li class="flex justify-between gap-4"><span>Dibayar</span><span class="font-bold">{{ $orderCounts['paid'] }}</span></li>
                    <li class="flex justify-between gap-4"><span>Selesai</span><span class="font-bold">{{ $orderCounts['completed'] }}</span></li>
                    <li class="flex justify-between gap-4"><span>Dibatalkan</span><span class="font-bold">{{ $orderCounts['cancelled'] }}</span></li>
                </ul>
            </section>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('admin.orders.index') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2">Lihat semua pesanan</a>
            <a href="{{ route('admin.products.index') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">Kelola produk</a>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
            <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Pesanan terbaru">
                <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Pesanan terbaru</h2>
                @if ($latestOrders->isEmpty())
                    <p class="mt-3 text-sm leading-6 text-muted">Belum ada pesanan.</p>
                @else
                    <ul class="mt-3 space-y-3">
                        @foreach ($latestOrders as $order)
                            <li class="flex items-start justify-between gap-4 border-t border-ink/30 pt-3 text-sm leading-6 first:border-t-0 first:pt-0">
                                <span class="min-w-0">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-ink underline decoration-ink/40 underline-offset-2">{{ $order->order_number }}</a>
                                    <span class="block text-muted">{{ $order->created_at->format('d/m/Y H:i') }} &middot; {{ $order->buyer_name ?? $order->user?->name ?? 'Pelanggan' }}</span>
                                </span>
                                <span class="shrink-0 text-right">
                                    <span class="block font-bold text-ink">{{ $order->formattedTotal() }}</span>
                                    <span class="block text-muted">{{ $order->orderStatusLabel() }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Stok rendah">
                <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Stok rendah (5 atau kurang)</h2>
                @if ($lowStock->isEmpty())
                    <p class="mt-3 text-sm leading-6 text-muted">Tidak ada produk dengan stok rendah.</p>
                @else
                    <ul class="mt-3 space-y-3">
                        @foreach ($lowStock as $product)
                            <li class="flex items-start justify-between gap-4 border-t border-ink/30 pt-3 text-sm leading-6 first:border-t-0 first:pt-0">
                                <span class="min-w-0">
                                    <span class="block font-bold text-ink">{{ $product->name }}</span>
                                    <span class="block text-muted">{{ $product->category?->name }}</span>
                                </span>
                                <a href="{{ route('admin.products.edit', $product) }}" class="shrink-0 font-bold text-ink underline decoration-ink/40 underline-offset-2">Stok {{ $product->stock }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
