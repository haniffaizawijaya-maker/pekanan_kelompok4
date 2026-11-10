<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold leading-8 text-ink">Detail pesanan</h1>

        @include('partials.flash')

        <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
            <div class="space-y-4">
                <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Rincian pesanan">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Nomor pesanan</p>
                    <p class="mt-1 text-xl font-bold text-ink">{{ $order->order_number }}</p>
                    <p class="mt-1 text-sm text-muted">Dibuat {{ $order->created_at->format('d/m/Y H:i') }}</p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-mustard px-2 py-1 text-xs font-bold text-ink">{{ $order->orderStatusLabel() }}</span>
                        <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-beige px-2 py-1 text-xs font-bold text-ink">{{ $order->paymentStatusLabel() }}</span>
                    </div>
                </section>

                <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Data pembeli">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Data pembeli</h2>
                    <p class="mt-3 text-base leading-6 text-ink"><span class="font-bold">Nama:</span> {{ $order->buyer_name ?? $order->user?->name ?? 'Pelanggan' }}</p>
                    <p class="mt-1 text-base leading-6 text-ink"><span class="font-bold">Email pengiriman:</span> {{ $order->buyer_email ?? $order->user?->email ?? 'Tidak ada email' }}</p>
                    <p class="mt-1 text-base leading-6 text-ink"><span class="font-bold">Akun:</span> {{ $order->user ? $order->user->name.' ('.$order->user->email.')' : 'Akun sudah dihapus' }}</p>
                    @if ($order->notes)
                        <p class="mt-1 text-base leading-6 text-ink"><span class="font-bold">Catatan:</span> {{ $order->notes }}</p>
                    @endif
                </section>

                <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Item pesanan">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Item pesanan</h2>
                    <ul class="mt-3 space-y-3">
                        @foreach ($order->items as $item)
                            <li class="flex items-start justify-between gap-4 border-t border-ink/30 pt-3 text-sm leading-6 first:border-t-0 first:pt-0">
                                <span class="min-w-0">
                                    <span class="block font-bold text-ink">{{ $item->product_name }}</span>
                                    <span class="block text-muted">{{ $item->quantity }} x Rp{{ number_format($item->price_at_checkout, 0, ',', '.') }}</span>
                                </span>
                                <span class="shrink-0 font-bold text-ink">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                @php($payment = $order->payments->first())
                @if ($payment)
                    <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Pembayaran">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Pembayaran</h2>
                        <dl class="mt-3 space-y-1 text-sm leading-6 text-ink">
                            <div class="flex justify-between gap-4"><dt class="text-muted">Gateway</dt><dd class="font-bold">{{ $payment->gateway }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-muted">Status pembayaran</dt><dd class="font-bold">{{ $payment->statusLabel() }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-muted">Nominal</dt><dd class="font-bold">Rp{{ number_format($payment->amount, 0, ',', '.') }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-muted">Referensi Tripay</dt><dd class="font-bold">{{ $payment->reference ?? 'Belum ada' }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-muted">Merchant ref</dt><dd class="font-bold">{{ $payment->merchant_ref ?? 'Belum ada' }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-muted">QR kedaluwarsa</dt><dd class="font-bold">{{ $order->qr_expires_at ? $order->qr_expires_at->format('d/m/Y H:i') : 'Belum ada' }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-muted">Dibayar pada</dt><dd class="font-bold">{{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : 'Belum dibayar' }}</dd></div>
                        </dl>
                        @if ($payment->status === 'refunded')
                            <p class="mt-3 text-sm leading-6 text-ink">Pembayaran ini telah dikembalikan di Tripay. Status pesanan tidak berubah; rekonsiliasi dilakukan manual di dashboard Tripay.</p>
                        @endif
                    </section>
                @endif
            </div>

            <aside class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5 lg:sticky lg:top-6" aria-label="Ringkasan dan aksi">
                <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Ringkasan</h2>
                <div class="mt-3 flex justify-between gap-4 border-t-[1.5px] border-ink pt-3 text-base">
                    <span class="text-muted">Subtotal</span>
                    <span class="font-bold text-ink">{{ $order->formattedSubtotal() }}</span>
                </div>
                <div class="mt-2 flex justify-between gap-4 border-t border-ink/30 pt-2 text-base">
                    <span class="text-muted">Ongkos kirim</span>
                    <span class="font-bold text-ink">Rp0</span>
                </div>
                <div class="mt-2 flex justify-between gap-4 border-t-[1.5px] border-ink pt-2 text-base">
                    <span class="text-muted">Total</span>
                    <span class="font-bold text-ink">{{ $order->formattedTotal() }}</span>
                </div>

                @if ($order->payment_status !== 'paid')
                    <a
                        href="{{ route('orders.pay', $order) }}"
                        class="mt-4 flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2"
                    >
                        Lihat halaman pembayaran
                    </a>
                @endif

                @if ($order->order_status === 'paid')
                    <form method="POST" action="{{ route('admin.orders.complete', $order) }}" onsubmit="return confirm('Tandai pesanan ini selesai?');">
                        @csrf
                        <button
                            type="submit"
                            class="mt-4 flex min-h-[44px] w-full items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2"
                        >
                            Tandai selesai
                        </button>
                    </form>
                @endif

                @if ($canCancel)
                    <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Batalkan pesanan ini? Stok item akan dikembalikan.');">
                        @csrf
                        <button
                            type="submit"
                            class="mt-4 flex min-h-[44px] w-full items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
                        >
                            Batalkan pesanan
                        </button>
                    </form>
                @elseif ($cancelReason)
                    <div class="mt-4 rounded-[6px] border-[1.5px] border-ink bg-parchment px-4 py-3 text-sm leading-6 text-ink">
                        {{ $cancelReason }}
                    </div>
                @endif

                <a
                    href="{{ route('admin.orders.index') }}"
                    class="mt-4 flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
                >
                    Kembali ke daftar pesanan
                </a>
            </aside>
        </div>
    </div>
</x-app-layout>
