<div>
    <h1 class="text-2xl font-bold leading-8 text-ink">Keranjang</h1>
    <p class="mt-1 text-base leading-6 text-muted" aria-live="polite">
        @if ($totalQuantity > 0)
            {{ $totalQuantity }} item di keranjang.
        @else
            Keranjang tersimpan di akun, bukan di perangkat ini saja.
        @endif
    </p>

    @if ($notices !== [])
        <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm leading-6 text-ink" role="status">
            @foreach ($notices as $notice)
                <p>{{ $notice }}</p>
            @endforeach
        </div>
    @endif

    @if (session('cart_notice'))
        <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm leading-6 text-ink" role="status">
            {{ session('cart_notice') }}
        </div>
    @endif

    @if ($message)
        <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm leading-6 text-ink" role="status">
            {{ $message }}
        </div>
    @endif

    <div wire:loading class="mt-4 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm text-ink" role="status">
        Memperbarui keranjang...
    </div>

    @if ($items->isEmpty())
        <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card px-5 py-10 text-center">
            <h2 class="text-xl font-bold text-ink">Keranjang masih kosong</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-muted">
                Belum ada produk di keranjang. Cari produk yang disuka, lalu tambahkan ke keranjang.
            </p>
            <a
                href="{{ route('catalog.index') }}"
                class="mt-5 inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2"
            >
                Lihat katalog
            </a>
        </div>
    @else
        <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
            <div class="space-y-4">
                @foreach ($items as $item)
                    <article class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5">
                        <div class="flex flex-row gap-4">
                            <a href="{{ route('products.show', $item->product->slug) }}" class="block shrink-0 self-start border-[1.5px] border-ink" tabindex="-1" aria-hidden="true">
                                @include('partials.product-image', ['src' => $item->product->displayImageUrl(), 'alt' => $item->product->name, 'wrapperClass' => 'aspect-[4/5] w-24 sm:w-32'])
                            </a>

                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">{{ $item->product->category->name }}</p>
                                <h2 class="mt-1 font-sans text-base font-bold leading-6 text-ink sm:text-lg">
                                    <a href="{{ route('products.show', $item->product->slug) }}" class="hover:underline focus:outline-none focus:ring-2 focus:ring-ink">
                                        {{ $item->product->name }}
                                    </a>
                                </h2>
                                <p class="mt-1 text-sm text-muted">{{ $item->formattedUnitPrice() }} per item</p>
                                <p class="mt-1 text-sm">
                                    @if ($item->product->isOutOfStock())
                                        <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-beige px-2 py-1 text-xs font-bold text-ink">Stok habis</span>
                                    @else
                                        <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-mustard px-2 py-1 text-xs font-bold text-ink">Stok tersedia, {{ $item->product->stock }} tersedia</span>
                                    @endif
                                </p>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <label class="sr-only" for="jumlah-{{ $item->id }}">Jumlah {{ $item->product->name }}</label>
                                    <button
                                        type="button"
                                        wire:click="decrement({{ $item->id }})"
                                        wire:loading.attr="disabled"
                                        aria-label="Kurangi jumlah {{ $item->product->name }}"
                                        @disabled($item->quantity <= 1)
                                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card text-lg font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        -
                                    </button>
                                    <input
                                        id="jumlah-{{ $item->id }}"
                                        type="number"
                                        min="1"
                                        max="{{ $item->product->stock }}"
                                        value="{{ $item->quantity }}"
                                        wire:change="setQuantity({{ $item->id }}, $event.target.value)"
                                        class="h-11 w-16 rounded-[6px] border-[1.5px] border-ink bg-card px-2 text-center text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink"
                                    >
                                    <button
                                        type="button"
                                        wire:click="increment({{ $item->id }})"
                                        wire:loading.attr="disabled"
                                        aria-label="Tambah jumlah {{ $item->product->name }}"
                                        @disabled($item->quantity >= $item->product->stock)
                                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card text-lg font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        +
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="remove({{ $item->id }})"
                                        wire:loading.attr="disabled"
                                        aria-label="Hapus {{ $item->product->name }} dari keranjang"
                                        class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
                                    >
                                        Hapus
                                    </button>
                                </div>

                                <p class="mt-3 text-lg font-bold text-ink">{{ $item->formattedSubtotal() }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <aside class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5 lg:sticky lg:top-6" aria-label="Ringkasan belanja">
                <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Ringkasan</h2>
                <div class="mt-3 flex justify-between gap-4 border-t-[1.5px] border-ink pt-3 text-base">
                    <span class="text-muted">Subtotal</span>
                    <span class="font-bold text-ink">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="mt-2 flex justify-between gap-4 border-t border-ink/30 pt-2 text-base">
                    <span class="text-muted">Total</span>
                    <span class="font-bold text-ink">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <p class="mt-2 text-sm leading-6 text-muted">Tidak ada ongkos kirim untuk produk digital. Detail pengiriman diatur saat checkout.</p>

                <div class="mt-4 rounded-[6px] border-[1.5px] border-ink bg-parchment px-4 py-3 text-sm leading-6 text-ink">
                    Buat pesanan di checkout, lalu bayar dengan QRIS dari halaman detail pesanan.
                </div>

                <a
                    href="{{ route('checkout.index') }}"
                    class="mt-4 flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2"
                >
                    Lanjut ke checkout
                </a>

                <a
                    href="{{ route('catalog.index') }}"
                    class="mt-3 flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
                >
                    Lanjut belanja
                </a>
            </aside>
        </div>
    @endif
</div>
