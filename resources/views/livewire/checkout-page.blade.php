{{-- Satu root <div> stabil. View Livewire tidak boleh diawali @if
     kondisional dengan dua root berbeda (lihat komentar di
     livewire/cart-counter.blade.php): morph menumpuk root baru di
     dalam root lama dan komponen hilang dari registry. --}}
<div>
    <h1 class="text-2xl font-bold leading-8 text-ink">Checkout</h1>
    <p class="mt-1 text-base leading-6 text-muted">
        Produk digital dikirim ke email setelah pembayaran terverifikasi. Bayar dengan QRIS dari halaman detail pesanan setelah pesanan dibuat.
    </p>

    @if ($previewNotices !== [])
        <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm leading-6 text-ink" role="status">
            @foreach ($previewNotices as $notice)
                <p>{{ $notice }}</p>
            @endforeach
        </div>
    @endif

    @if ($lines === [])
        <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card px-5 py-10 text-center">
            <h2 class="text-xl font-bold text-ink">Keranjang masih kosong</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-muted">
                Belum ada produk yang bisa di-checkout. Tambahkan produk dulu, lalu kembali ke halaman ini.
            </p>
            <a
                href="{{ route('cart.index') }}"
                class="mt-5 inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2"
            >
                Lihat keranjang
            </a>
        </div>
    @else
        <form wire:submit="submit" class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
            <section class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" aria-label="Data pengiriman">
                <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Data pengiriman</h2>
                <p class="mt-2 text-sm leading-6 text-muted">
                    Produk digital dikirim ke email di bawah ini. Tidak ada alamat pengiriman.
                </p>

                <div class="mt-4">
                    <x-input-label for="name" value="Nama lengkap" />
                    <x-text-input
                        id="name"
                        type="text"
                        wire:model="name"
                        required
                        maxlength="100"
                        autocomplete="name"
                        class="mt-1 block w-full min-h-[44px] px-3"
                    />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="email" value="Email pengiriman" />
                    <x-text-input
                        id="email"
                        type="email"
                        wire:model="email"
                        required
                        maxlength="255"
                        autocomplete="email"
                        class="mt-1 block w-full min-h-[44px] px-3"
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="notes" value="Catatan (opsional)" />
                    <textarea
                        id="notes"
                        wire:model="notes"
                        rows="3"
                        maxlength="500"
                        class="mt-1 block w-full rounded-[6px] border-[1.5px] border-ink bg-card px-3 py-2 text-ink placeholder:text-muted focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink"
                        placeholder="Tulis catatan untuk pesanan ini bila ada."
                    ></textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </section>

            <aside class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5 lg:sticky lg:top-6" aria-label="Ringkasan pesanan">
                <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Ringkasan pesanan</h2>

                <ul class="mt-3 space-y-3 border-t-[1.5px] border-ink pt-3">
                    @foreach ($lines as $line)
                        <li class="flex items-start justify-between gap-4 text-sm leading-6">
                            <span class="min-w-0">
                                <span class="block font-bold text-ink">{{ $line['product_name'] }}</span>
                                <span class="block text-muted">{{ $line['quantity'] }} x Rp{{ number_format($line['price'], 0, ',', '.') }}</span>
                            </span>
                            <span class="shrink-0 font-bold text-ink">Rp{{ number_format($line['subtotal'], 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-3 flex justify-between gap-4 border-t-[1.5px] border-ink pt-3 text-base">
                    <span class="text-muted">Subtotal</span>
                    <span class="font-bold text-ink">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="mt-2 flex justify-between gap-4 border-t border-ink/30 pt-2 text-base">
                    <span class="text-muted">Ongkos kirim</span>
                    <span class="font-bold text-ink">Rp0</span>
                </div>
                <div class="mt-2 flex justify-between gap-4 border-t-[1.5px] border-ink pt-2 text-base">
                    <span class="text-muted">Total</span>
                    <span class="font-bold text-ink">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>

                <p class="mt-2 text-sm leading-6 text-muted">
                    {{ $totalQuantity }} item. Tidak ada ongkos kirim untuk produk digital.
                </p>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="mt-4 flex min-h-[44px] w-full items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Buat pesanan
                </button>

                <div wire:loading class="mt-3 text-sm text-muted" role="status">
                    Memproses pesanan...
                </div>

                <a
                    href="{{ route('cart.index') }}"
                    class="mt-3 flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
                >
                    Kembali ke keranjang
                </a>
            </aside>
        </form>
    @endif
</div>
