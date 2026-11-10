<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold leading-8 text-ink">Detail pesanan</h1>

        @if ($showBanner ?? false)
            <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm leading-6 text-ink" role="status">
                Pesanan berhasil dibuat.
            </div>
        @endif

        @include('partials.flash')

        <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
            <div class="space-y-4">
                <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Struk pesanan">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Struk pesanan</p>
                    <p class="mt-1 text-xl font-bold text-ink">{{ $order->order_number }}</p>
                    <p class="mt-1 text-sm text-muted">Dibuat {{ $order->created_at->format('d/m/Y H:i') }} WIB</p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-mustard px-2 py-1 text-xs font-bold text-ink">{{ $order->orderStatusLabel() }}</span>
                        <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-beige px-2 py-1 text-xs font-bold text-ink">{{ $order->paymentStatusLabel() }}</span>
                    </div>
                    @if (($order->payments->first()?->status) === 'refunded')
                        <p class="mt-3 text-sm leading-6 text-ink">Pembayaran untuk pesanan ini telah dikembalikan. Status pesanan tidak berubah; hubungi admin bila ada pertanyaan.</p>
                    @endif

                    <ul class="mt-4 space-y-3">
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

                    <dl class="mt-4 text-base">
                        <div class="flex justify-between gap-4 border-t-[1.5px] border-ink pt-3">
                            <dt class="text-muted">Subtotal</dt>
                            <dd class="font-bold text-ink">{{ $order->formattedSubtotal() }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-ink/30 pt-2">
                            <dt class="text-muted">Ongkos kirim</dt>
                            <dd class="font-bold text-ink">Rp0</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t-[1.5px] border-ink pt-2">
                            <dt class="text-muted">Total</dt>
                            <dd class="font-bold text-ink">{{ $order->formattedTotal() }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-ink/30 pt-2 text-sm leading-6">
                            <dt class="text-muted">Metode pembayaran</dt>
                            <dd class="font-bold text-ink">QRIS2</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-ink/30 pt-2 text-sm leading-6">
                            <dt class="text-muted">Referensi Tripay</dt>
                            <dd class="font-bold text-ink">{{ $order->tripay_reference ?? $order->gateway_reference ?? $order->payments->first()?->reference ?? 'Belum ada' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-ink/30 pt-2 text-sm leading-6">
                            <dt class="text-muted">Waktu bayar</dt>
                            <dd class="font-bold text-ink">{{ $order->paid_at ? $order->paid_at->format('d/m/Y H:i').' WIB' : 'Belum dibayar' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Status pengiriman">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Status pengiriman</h2>
                    <p class="mt-3 text-base leading-7 text-ink">
                        @if ($order->order_status === 'pending')
                            Menunggu pembayaran. Setelah pembayaran terverifikasi, pesanan diproses dan produk digital dikirim ke email tujuan.
                        @elseif ($order->order_status === 'paid')
                            Pembayaran diterima. Pesanan sedang diproses admin dan produk digital akan dikirim ke email tujuan.
                        @elseif ($order->order_status === 'completed')
                            Pesanan selesai. Produk digital sudah dikirim ke email tujuan.
                        @elseif ($order->order_status === 'cancelled')
                            Pesanan dibatalkan. Tidak ada pembayaran yang diproses untuk pesanan ini.
                        @else
                            Status pesanan: {{ $order->orderStatusLabel() }}.
                        @endif
                    </p>
                    <p class="mt-1 text-sm leading-6 text-muted">Email tujuan: {{ $order->buyer_email ?? $order->user?->email ?? 'Tidak ada email' }}</p>
                </section>

                <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Data pembeli">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Data pembeli</h2>
                    <p class="mt-3 text-base leading-6 text-ink"><span class="font-bold">Nama:</span> {{ $order->buyer_name ?? $order->user?->name ?? 'Pelanggan' }}</p>
                    <p class="mt-1 text-base leading-6 text-ink"><span class="font-bold">Email pengiriman:</span> {{ $order->buyer_email ?? $order->user?->email ?? 'Tidak ada email' }}</p>
                    @if ($order->notes)
                        <p class="mt-1 text-base leading-6 text-ink"><span class="font-bold">Catatan:</span> {{ $order->notes }}</p>
                    @endif
                </section>
            </div>

            <aside class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5 lg:sticky lg:top-6" aria-label="Tindakan pesanan">
                <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Tindakan</h2>

                @if ($order->payment_status !== 'paid')
                    <a
                        href="{{ route('orders.pay', $order) }}"
                        class="mt-4 flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2"
                    >
                        Lihat pembayaran
                    </a>
                @endif

                @if ($canCancel ?? false)
                    <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Batalkan pesanan ini? Stok item akan dikembalikan.');">
                        @csrf
                        <button
                            type="submit"
                            class="mt-4 flex min-h-[44px] w-full items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
                        >
                            Batalkan pesanan
                        </button>
                    </form>
                @elseif ($cancelReason ?? null)
                    <div class="mt-4 rounded-[6px] border-[1.5px] border-ink bg-parchment px-4 py-3 text-sm leading-6 text-ink">
                        {{ $cancelReason }}
                    </div>
                @endif

                <a
                    href="{{ route('orders.index') }}"
                    class="mt-4 flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
                >
                    Kembali ke daftar pesanan
                </a>

                <a
                    href="{{ route('catalog.index') }}"
                    class="mt-4 flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2"
                >
                    Kembali ke katalog
                </a>
                <a
                    href="{{ route('cart.index') }}"
                    class="mt-3 flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
                >
                    Lihat keranjang
                </a>
            </aside>
        </div>
    </div>
</x-app-layout>
