<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold leading-8 text-ink">Pesanan saya</h1>

        @include('partials.flash')

        @include('partials.order-status-tabs', ['tabsRoute' => 'orders.index', 'statusCounts' => $statusCounts])

        <form method="GET" action="{{ route('orders.index') }}" class="mt-5 flex flex-wrap items-end gap-3" role="search">
            <div class="min-w-[220px] flex-1">
                <label for="q" class="block text-[11px] font-bold uppercase tracking-wider text-muted">Cari nomor pesanan</label>
                <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Contoh: MC-20261031" class="mt-1 h-11 w-full rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-base text-ink placeholder:text-muted focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink">
            </div>
            <div>
                <label for="order_status" class="block text-[11px] font-bold uppercase tracking-wider text-muted">Status pesanan</label>
                <select id="order_status" name="order_status" class="mt-1 h-11 rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink">
                    <option value="">Semua status</option>
                    <option value="pending" @selected(request('order_status') === 'pending')>Menunggu pembayaran</option>
                    <option value="paid" @selected(request('order_status') === 'paid')>Dibayar</option>
                    <option value="completed" @selected(request('order_status') === 'completed')>Selesai</option>
                    <option value="cancelled" @selected(request('order_status') === 'cancelled')>Dibatalkan</option>
                </select>
            </div>
            <button type="submit" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-ink px-5 text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2">Filter</button>
            @if (request('q') || request('order_status'))
                <a href="{{ route('orders.index') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">Hapus filter</a>
            @endif
        </form>

        @if ($orders->isEmpty())
            <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card p-5">
                <p class="text-base leading-7 text-ink">Belum ada pesanan yang cocok.</p>
                <a href="{{ route('catalog.index') }}" class="mt-4 inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2">Buka katalog</a>
            </div>
        @else
            <ul class="mt-5 space-y-4">
                @foreach ($orders as $order)
                    <li class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-lg font-bold text-ink">{{ $order->order_number }}</p>
                                <p class="mt-1 text-sm text-muted">Dibuat {{ $order->created_at->format('d/m/Y H:i') }}</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-mustard px-2 py-1 text-xs font-bold text-ink">{{ $order->orderStatusLabel() }}</span>
                                    <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-beige px-2 py-1 text-xs font-bold text-ink">{{ $order->paymentStatusLabel() }}</span>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="font-bold text-ink">{{ $order->formattedTotal() }}</p>
                                <a href="{{ route('orders.show', $order) }}" class="mt-2 inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">Lihat detail</a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-5">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
