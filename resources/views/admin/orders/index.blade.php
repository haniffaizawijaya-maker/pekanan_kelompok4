<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold leading-8 text-ink">Pesanan</h1>

        @include('partials.flash')

        @include('partials.order-status-tabs', ['tabsRoute' => 'admin.orders.index', 'statusCounts' => $statusCounts])

        <form method="GET" action="{{ route('admin.orders.index') }}" class="mt-5 flex flex-wrap items-end gap-3" role="search">
            <div class="min-w-[220px] flex-1">
                <label for="q" class="block text-[11px] font-bold uppercase tracking-wider text-muted">Cari nomor, nama, atau email pembeli</label>
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
            <div>
                <label for="payment_status" class="block text-[11px] font-bold uppercase tracking-wider text-muted">Status pembayaran</label>
                <select id="payment_status" name="payment_status" class="mt-1 h-11 rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink">
                    <option value="">Semua status</option>
                    <option value="unpaid" @selected(request('payment_status') === 'unpaid')>Belum dibayar</option>
                    <option value="pending" @selected(request('payment_status') === 'pending')>Menunggu pembayaran</option>
                    <option value="paid" @selected(request('payment_status') === 'paid')>Sudah dibayar</option>
                    <option value="failed" @selected(request('payment_status') === 'failed')>Gagal</option>
                </select>
            </div>
            <button type="submit" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-ink px-5 text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2">Filter</button>
            @if (request('q') || request('order_status') || request('payment_status'))
                <a href="{{ route('admin.orders.index') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">Hapus filter</a>
            @endif
        </form>

        @if ($orders->isEmpty())
            <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card p-5">
                <p class="text-base leading-7 text-ink">Belum ada pesanan yang cocok.</p>
            </div>
        @else
            <div class="mt-5 overflow-x-auto rounded-[6px] border-[1.5px] border-ink bg-card">
                <table class="w-full min-w-[720px] text-left text-sm leading-6">
                    <thead>
                        <tr class="border-b-[1.5px] border-ink">
                            <th scope="col" class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Nomor pesanan</th>
                            <th scope="col" class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Pembeli</th>
                            <th scope="col" class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Tanggal</th>
                            <th scope="col" class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Total</th>
                            <th scope="col" class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Status pesanan</th>
                            <th scope="col" class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Status pembayaran</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr class="border-t border-ink/30 first:border-t-0">
                                <td class="px-4 py-3 font-bold text-ink">{{ $order->order_number }}</td>
                                <td class="px-4 py-3 text-ink">{{ $order->buyer_name ?? $order->user?->name ?? 'Pelanggan' }}<span class="block text-muted">{{ $order->buyer_email ?? $order->user?->email ?? 'Tidak ada email' }}</span></td>
                                <td class="px-4 py-3 text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 font-bold text-ink">{{ $order->formattedTotal() }}</td>
                                <td class="px-4 py-3 text-ink">{{ $order->orderStatusLabel() }}</td>
                                <td class="px-4 py-3 text-ink">{{ $order->paymentStatusLabel() }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-4 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">Lihat</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
