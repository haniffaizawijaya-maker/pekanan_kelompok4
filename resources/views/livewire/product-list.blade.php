<div>
    <form class="rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5" wire:submit.prevent>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label for="cari-produk" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">Cari produk</label>
                <input
                    id="cari-produk"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari Netflix, Canva, dan lainnya"
                    class="h-11 w-full rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-base text-ink placeholder:text-muted focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink"
                >
            </div>

            <div class="lg:w-56">
                <label for="urutkan" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">Urutkan</label>
                <select
                    id="urutkan"
                    wire:model.live="sort"
                    class="h-11 w-full rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink"
                >
                    <option value="terbaru">Terbaru</option>
                    <option value="harga-terendah">Harga terendah</option>
                    <option value="harga-tertinggi">Harga tertinggi</option>
                    <option value="nama">Nama A sampai Z</option>
                </select>
            </div>
        </div>
    </form>

    <div class="mt-5">
        <div>
            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-muted" id="label-kategori">Kategori</p>
            <div class="flex flex-wrap gap-2" role="group" aria-labelledby="label-kategori">
                <button
                    type="button"
                    wire:click="setCategory('semua')"
                    aria-pressed="{{ $category === 'semua' ? 'true' : 'false' }}"
                    class="min-h-[44px] rounded-[6px] border-[1.5px] border-ink px-4 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-ink {{ $category === 'semua' ? 'bg-ink text-white' : 'bg-card text-ink hover:bg-parchment' }}"
                >
                    Semua
                </button>
                @foreach ($categories as $item)
                    <button
                        type="button"
                        wire:click="setCategory('{{ $item->slug }}')"
                        aria-pressed="{{ $category === $item->slug ? 'true' : 'false' }}"
                        class="min-h-[44px] rounded-[6px] border-[1.5px] border-ink px-4 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-ink {{ $category === $item->slug ? 'bg-ink text-white' : 'bg-card text-ink hover:bg-parchment' }}"
                    >
                        {{ $item->name }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <p class="mt-5 text-sm text-muted" aria-live="polite">Menampilkan {{ $products->total() }} produk.</p>

    @if ($cartMessage)
        <div class="mt-4 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm leading-6 text-ink" role="status">
            {{ $cartMessage }}
            <a href="{{ route('cart.index') }}" class="font-bold text-accentdark underline hover:underline focus:outline-none focus:ring-2 focus:ring-ink">Lihat keranjang</a>
        </div>
    @endif

    @if ($cartError)
        <p class="mt-4 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm leading-6 text-ink" role="alert">{{ $cartError }}</p>
    @endif

    <div wire:loading class="mt-3 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm text-ink" role="status">
        Memuat produk...
    </div>

    @if ($products->isEmpty())
        <div class="mt-4 rounded-[6px] border-[1.5px] border-ink bg-card px-5 py-10 text-center">
            <h2 class="text-xl font-bold text-ink">Produk tidak ditemukan</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-muted">
                Tidak ada produk yang cocok dengan pencarian dan filter yang dipakai. Coba kata kunci lain atau reset filter di bawah ini.
            </p>
            <button
                type="button"
                wire:click="resetFilters"
                class="mt-5 inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2"
            >
                Reset filter
            </button>
        </div>
    @else
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach ($products as $product)
                <article class="flex h-full flex-col overflow-hidden rounded-[6px] border-[1.5px] border-ink bg-card">
                    <a href="{{ route('products.show', $product->slug) }}" class="block border-b-[1.5px] border-ink" tabindex="-1" aria-hidden="true">
                        @include('partials.product-image', ['src' => $product->displayImageUrl(), 'alt' => $product->name, 'wrapperClass' => 'aspect-[3/4] w-full'])
                    </a>
                    <div class="flex flex-1 flex-col p-3 sm:p-4">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted">{{ $product->category->name }}</p>
                        <h2 class="mt-1 text-left font-sans text-base font-bold leading-6 text-ink sm:text-lg">
                            <a href="{{ route('products.show', $product->slug) }}" class="hover:underline focus:outline-none focus:ring-2 focus:ring-ink">
                                {{ $product->name }}
                            </a>
                        </h2>
                        <p class="mt-2 text-lg font-bold text-ink">{{ $product->formattedPrice() }}</p>
                        <p class="mt-1 text-sm">
                            @if ($product->isOutOfStock())
                                <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-beige px-2 py-1 text-xs font-bold text-ink">Stok habis</span>
                            @else
                                <span class="inline-block rounded-[6px] border-[1.5px] border-ink bg-mustard px-2 py-1 text-xs font-bold text-ink">Stok tersedia</span>
                            @endif
                        </p>
                        <div class="mt-auto flex flex-col gap-2 pt-4">
                            @if ($product->isOutOfStock())
                                <button
                                    type="button"
                                    disabled
                                    class="flex min-h-[44px] cursor-not-allowed items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-beige px-4 text-sm font-bold text-muted"
                                >
                                    Stok habis
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="addToCart({{ $product->id }})"
                                    wire:loading.attr="disabled"
                                    aria-label="Tambah {{ $product->name }} ke keranjang"
                                    class="flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-accent px-4 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2"
                                >
                                    Tambah ke Keranjang
                                </button>
                            @endif
                            <a
                                href="{{ route('products.show', $product->slug) }}"
                                class="flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card px-4 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
                            >
                                Lihat detail
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @endif
</div>
