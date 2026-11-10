<div @if ($showPoll) wire:poll.10s="refreshStatus" @endif>
    <h1 class="text-2xl font-bold leading-8 text-ink">Pembayaran</h1>
    <p class="mt-1 text-base leading-6 text-muted">Pesanan {{ $order->order_number }}</p>

    <div class="mt-3 flex flex-wrap gap-2">
        <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-mustard px-2 py-1 text-xs font-bold text-ink">{{ $order->orderStatusLabel() }}</span>
        <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-beige px-2 py-1 text-xs font-bold text-ink">{{ $order->paymentStatusLabel() }}</span>
    </div>

    <section class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Rincian pembayaran">
        <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Rincian pembayaran</h2>
        <p class="mt-2 text-lg font-bold text-ink">Nominal yang harus dibayar: {{ $order->formattedTotal() }}</p>
        <p class="mt-1 text-sm leading-6 text-muted">Bayar tepat sebesar nominal ini; jangan ditambah atau dikurangi.</p>
        @if ($expiresAt && ! $isPaid)
            <p class="mt-1 text-sm leading-6 text-muted">Berlaku sampai {{ $expiresAt->format('d/m/Y H:i') }} WIB. Halaman ini memeriksa status pembayaran secara berkala.</p>
        @endif
        @if ($merchantFee !== null)
            <p class="mt-1 text-sm leading-6 text-muted">Biaya layanan Rp{{ number_format($merchantFee, 0, ',', '.') }} ditanggung penjual dan tidak menambah total yang kamu bayar.</p>
        @endif
    </section>

    @if ($message)
        <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm leading-6 text-ink" role="status">
            {{ $message }}
        </div>
    @endif

    @if ($error)
        <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm leading-6 text-ink" role="alert">
            {{ $error }}
        </div>
    @endif

    <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
        <div class="space-y-4">
            @if ($credentialsMissing)
                <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Kredensial belum diisi">
                    <h2 class="text-xl font-bold text-ink">Pembayaran belum bisa dibuat</h2>
                    <p class="mt-2 text-sm leading-6 text-muted">
                        Kredensial Tripay sandbox belum diisi di file .env lokal. Isi ketiga variabel ini, lalu muat ulang halaman ini: TRIPAY_MERCHANT_CODE, TRIPAY_API_KEY, dan TRIPAY_PRIVATE_KEY. Jangan pernah menempel kunci di chat atau di commit. Pesanan tetap tersimpan dan bisa dibayar setelah kredensial diisi.
                    </p>
                </section>
            @elseif ($isPaid)
                <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Pembayaran selesai">
                    <h2 class="text-xl font-bold text-ink">Pembayaran sudah terverifikasi</h2>
                    <p class="mt-2 text-sm leading-6 text-muted">
                        Pesanan ini sudah dibayar. Produk digital dikirim ke email setelah pembayaran terverifikasi.
                    </p>
                </section>
            @elseif ($payment !== null && $payment->reference !== null && ! $isExpired)
                <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Kode QR pembayaran">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Kode QR QRIS</h2>

                    @if ($payment->qr_url)
                        <p id="qr-gagal" hidden class="mt-4 text-sm leading-6 text-muted">Kode QR gagal dimuat. Coba cek status pembayaran di bawah ini.</p>
                        <img
                            src="{{ $payment->qr_url }}"
                            alt="Kode QR untuk membayar pesanan {{ $order->order_number }}"
                            onerror="this.style.display='none';document.getElementById('qr-gagal').hidden=false;"
                            class="mt-4 block w-full max-w-[320px] border-[1.5px] border-ink bg-white"
                        >
                    @elseif ($qrString)
                        <div class="mt-4 rounded-[6px] border-[1.5px] border-ink bg-parchment px-4 py-3">
                            <p class="text-sm leading-6 text-muted">Tripay tidak mengirim gambar kode QR untuk transaksi ini. Pakai kode QR di bawah ini di aplikasi pembayaran yang mendukung QRIS:</p>
                            <p class="mt-2 break-all font-mono text-xs leading-5 text-ink">{{ $qrString }}</p>
                        </div>
                    @else
                        <p class="mt-4 text-sm leading-6 text-muted">
                            Kode QR belum tersedia dari Tripay. Coba cek status pembayaran di bawah ini.
                        </p>
                    @endif

                    <p class="mt-4 text-lg font-bold text-ink">Total yang dibayar: {{ $order->formattedTotal() }}</p>

                    @if ($payment->reference)
                        <p class="mt-1 text-sm leading-6 text-muted">Referensi Tripay: {{ $payment->reference }}</p>
                    @endif
                </section>

                <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Cara membayar">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Cara membayar</h2>

                    @if ($instructions !== [])
                        @foreach ($instructions as $group)
                            <h3 class="mt-4 font-bold text-ink">{{ $group['title'] }}</h3>
                            <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm leading-6 text-ink">
                                @foreach ($group['steps'] as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                        @endforeach
                    @else
                        <ol class="mt-3 list-decimal space-y-1 pl-5 text-sm leading-6 text-ink">
                            <li>Buka aplikasi pembayaran apa pun yang mendukung QRIS.</li>
                            <li>Pindai kode QR di atas.</li>
                            <li>Periksa nama merchant dan nominalnya sudah sesuai dengan total pesanan.</li>
                            <li>Konfirmasi pembayaran di aplikasi tersebut.</li>
                        </ol>
                    @endif
                </section>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        wire:click="checkStatus"
                        wire:loading.attr="disabled"
                        class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Cek status pembayaran
                    </button>
                </div>
            @elseif ($payment !== null && $payment->reference !== null && $isExpired)
                <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Kode QR kedaluwarsa">
                    <h2 class="text-xl font-bold text-ink">Kode QR sudah kedaluwarsa</h2>
                    <p class="mt-2 text-sm leading-6 text-muted">
                        Batas waktu pembayaran sudah lewat. Buat kode QR baru dengan nominal yang sama; kode lama tidak berlaku lagi.
                    </p>
                    <button
                        type="button"
                        wire:click="regenerate"
                        wire:loading.attr="disabled"
                        class="mt-4 inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Buat QR baru
                    </button>
                </section>
            @else
                <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Kode QR belum dibuat">
                    <h2 class="text-xl font-bold text-ink">Kode QR belum dibuat</h2>
                    <p class="mt-2 text-sm leading-6 text-muted">
                        Pembuatan kode pembayaran tidak berhasil. Pesanan tetap tersimpan dan bisa dicoba lagi tanpa membuat pesanan baru.
                    </p>
                    @if (! $credentialsMissing)
                        <button
                            type="button"
                            wire:click="retryCreate"
                            wire:loading.attr="disabled"
                            class="mt-4 inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            Coba buat kode QR lagi
                        </button>
                    @endif
                </section>
            @endif

            <a
                href="{{ route('orders.show', $order) }}"
                class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
            >
                Kembali ke detail pesanan
            </a>
        </div>

        <aside class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5 lg:sticky lg:top-6" aria-label="Ringkasan pesanan">
            <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Ringkasan</h2>
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
            <p class="mt-3 text-sm leading-6 text-muted">Tidak ada ongkos kirim untuk produk digital. Produk digital dikirim ke email setelah pembayaran terverifikasi.</p>
        </aside>
    </div>
</div>
