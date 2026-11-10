<div>
    <nav aria-label="Breadcrumb" class="text-sm text-muted">
        <ol class="flex flex-wrap items-center gap-1">
            <li>
                <a href="{{ route('catalog.index') }}" class="hover:underline focus:outline-none focus:ring-2 focus:ring-ink">Beranda</a>
            </li>
            <li aria-hidden="true">/</li>
            <li>{{ $product->category->name }}</li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-ink">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2 md:gap-8">
        <div class="overflow-hidden rounded-[6px] border-[1.5px] border-ink bg-card">
            @include('partials.product-image', ['src' => $product->displayImageUrl(), 'alt' => $product->name, 'wrapperClass' => 'aspect-[4/5] w-full', 'loading' => 'eager'])
        </div>

        <div class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-6">
            <p class="text-[11px] font-bold uppercase tracking-wider text-muted">{{ $product->category->name }}</p>
            <h1 class="mt-1 text-3xl font-bold leading-9 text-ink">{{ $product->name }}</h1>
            <p class="mt-3 text-2xl font-bold text-ink">{{ $product->formattedPrice() }}</p>
            <p class="mt-2 text-sm">
                @if ($product->isOutOfStock())
                    <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-beige px-2 py-1 text-xs font-bold text-ink">Stok habis</span>
                @else
                    <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-mustard px-2 py-1 text-xs font-bold text-ink">Stok tersedia</span>
                @endif
            </p>

            <h2 class="mt-6 text-xs font-bold uppercase tracking-wider text-muted">Deskripsi</h2>
            <p class="mt-1 font-sans text-base leading-7 text-ink">{{ $product->description }}</p>

            <dl class="mt-6 text-sm">
                <div class="flex justify-between gap-4 border-t-[1.5px] border-ink py-2">
                    <dt class="text-muted">Harga</dt>
                    <dd class="text-right font-bold text-ink">{{ $product->formattedPrice() }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-t border-ink/30 py-2">
                    <dt class="text-muted">Stok</dt>
                    <dd class="text-right font-bold text-ink">
                        @if ($product->isOutOfStock())
                            Habis
                        @else
                            {{ $product->stock }} tersedia
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4 border-t border-ink/30 py-2">
                    <dt class="text-muted">Kategori</dt>
                    <dd class="text-right font-bold text-ink">{{ $product->category->name }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-y-[1.5px] border-ink py-2">
                    <dt class="text-muted">Kode produk</dt>
                    <dd class="text-right font-bold text-ink">{{ $product->slug }}</dd>
                </div>
            </dl>

            @if ($cartMessage)
                <p class="mt-6 rounded-[6px] border-[1.5px] border-ink bg-parchment px-4 py-3 text-sm leading-6 text-ink" role="status">
                    {{ $cartMessage }}
                    <a href="{{ route('cart.index') }}" class="font-bold text-accentdark underline hover:underline focus:outline-none focus:ring-2 focus:ring-ink">Lihat keranjang</a>
                </p>
            @endif

            @if ($cartError)
                <p class="mt-6 rounded-[6px] border-[1.5px] border-ink bg-parchment px-4 py-3 text-sm leading-6 text-ink" role="alert">{{ $cartError }}</p>
            @endif

            @if ($product->isOutOfStock())
                <button
                    type="button"
                    disabled
                    class="mt-6 inline-flex min-h-[44px] cursor-not-allowed items-center rounded-[6px] border-[1.5px] border-ink bg-beige px-5 text-sm font-bold text-muted"
                >
                    Stok habis
                </button>
            @else
                <div class="mt-6">
                    <label for="jumlah" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">Jumlah</label>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="decreaseQuantity"
                            aria-label="Kurangi jumlah"
                            @disabled($quantity <= 1)
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card text-lg font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            -
                        </button>
                        <input
                            id="jumlah"
                            type="number"
                            min="1"
                            max="{{ $product->stock }}"
                            value="{{ $quantity }}"
                            wire:change="updateQuantityInput($event.target.value)"
                            class="h-11 w-16 rounded-[6px] border-[1.5px] border-ink bg-card px-2 text-center text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink"
                        >
                        <button
                            type="button"
                            wire:click="increaseQuantity"
                            aria-label="Tambah jumlah"
                            @disabled($quantity >= $product->stock)
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card text-lg font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            +
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-muted">Maksimal {{ $product->stock }} sesuai stok yang tersedia.</p>
                </div>

                <button
                    type="button"
                    wire:click="addToCart"
                    wire:loading.attr="disabled"
                    class="mt-4 inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2"
                >
                    Tambah ke Keranjang
                </button>
                @guest
                    <p class="mt-2 text-sm text-muted">Kamu akan diarahkan ke halaman masuk untuk memakai keranjang.</p>
                @endguest
            @endif

            <p class="mt-6 rounded-[6px] border-[1.5px] border-ink bg-parchment px-4 py-3 text-sm leading-6 text-ink">
                Produk digital dikirim ke email setelah pembayaran terverifikasi.
            </p>

            <a
                href="{{ route('catalog.index') }}"
                class="mt-4 inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
            >
                Kembali ke katalog
            </a>
        </div>
    </div>

    @if ($durationVariants->isNotEmpty())
        <section class="mt-8" aria-labelledby="durasi-lain">
            <h2 id="durasi-lain" class="text-xl font-bold text-ink">Durasi lain dari {{ $durationBaseName }}</h2>
            <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($durationVariants as $variant)
                    <li>
                        <a
                            href="{{ route('products.show', $variant->slug) }}"
                            class="flex min-h-[44px] items-center justify-between gap-4 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
                        >
                            <span class="text-base font-bold leading-6 text-ink">{{ $variant->name }}</span>
                            <span class="shrink-0 text-base font-bold text-ink">{{ $variant->formattedPrice() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($related->isNotEmpty())
        <section class="mt-8" aria-labelledby="produk-terkait">
            <h2 id="produk-terkait" class="text-xl font-bold text-ink">Produk lain di kategori {{ $product->category->name }}</h2>
            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($related as $item)
                    <article class="flex h-full flex-col overflow-hidden rounded-[6px] border-[1.5px] border-ink bg-card">
                        <a href="{{ route('products.show', $item->slug) }}" class="block border-b-[1.5px] border-ink" tabindex="-1" aria-hidden="true">
                            @include('partials.product-image', ['src' => $item->displayImageUrl(), 'alt' => $item->name, 'wrapperClass' => 'aspect-[3/4] w-full'])
                        </a>
                        <div class="flex flex-1 flex-col p-3 sm:p-4">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-muted">{{ $item->category->name }}</p>
                            <h3 class="mt-1 text-left font-sans text-base font-bold leading-6 text-ink">
                                <a href="{{ route('products.show', $item->slug) }}" class="hover:underline focus:outline-none focus:ring-2 focus:ring-ink">
                                    {{ $item->name }}
                                </a>
                            </h3>
                            <p class="mt-2 text-lg font-bold text-ink">{{ $item->formattedPrice() }}</p>
                            <div class="mt-auto pt-4">
                                <a
                                    href="{{ route('products.show', $item->slug) }}"
                                    class="flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card px-4 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
                                >
                                    Lihat detail
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
