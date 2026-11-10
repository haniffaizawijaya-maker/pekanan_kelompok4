<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <h1 class="text-2xl font-bold leading-8 text-ink">Produk</h1>
            <a href="{{ route('admin.products.create') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2">Tambah produk</a>
        </div>

        @include('partials.flash')

        <form method="GET" action="{{ route('admin.products.index') }}" class="mt-5 flex flex-wrap items-end gap-3" role="search">
            <div class="min-w-[220px] flex-1">
                <label for="q" class="block text-[11px] font-bold uppercase tracking-wider text-muted">Cari nama produk</label>
                <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Contoh: Netflix" class="mt-1 h-11 w-full rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-base text-ink placeholder:text-muted focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink">
            </div>
            <div>
                <label for="category_id" class="block text-[11px] font-bold uppercase tracking-wider text-muted">Kategori</label>
                <select id="category_id" name="category_id" class="mt-1 h-11 rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="is_active" class="block text-[11px] font-bold uppercase tracking-wider text-muted">Status</label>
                <select id="is_active" name="is_active" class="mt-1 h-11 rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink">
                    <option value="">Semua status</option>
                    <option value="1" @selected(request('is_active') === '1')>Aktif</option>
                    <option value="0" @selected(request('is_active') === '0')>Nonaktif</option>
                </select>
            </div>
            <button type="submit" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-ink px-5 text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2">Filter</button>
            @if (request('q') || request('category_id') || request('is_active') !== null)
                <a href="{{ route('admin.products.index') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">Hapus filter</a>
            @endif
        </form>

        @if ($products->isEmpty())
            <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card p-5">
                <p class="text-base leading-7 text-ink">Belum ada produk yang cocok.</p>
            </div>
        @else
            <div class="mt-5 overflow-x-auto rounded-[6px] border-[1.5px] border-ink bg-card">
                <table class="w-full min-w-[720px] text-left text-sm leading-6">
                    <thead>
                        <tr class="border-b-[1.5px] border-ink">
                            <th scope="col" class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Produk</th>
                            <th scope="col" class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Kategori</th>
                            <th scope="col" class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Harga</th>
                            <th scope="col" class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Stok</th>
                            <th scope="col" class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Status</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr class="border-t border-ink/30 first:border-t-0">
                                <td class="px-4 py-3">
                                    <span class="block font-bold text-ink">{{ $product->name }}</span>
                                    <span class="block text-muted">{{ $product->slug }}</span>
                                </td>
                                <td class="px-4 py-3 text-ink">{{ $product->category?->name }}</td>
                                <td class="px-4 py-3 font-bold text-ink">{{ $product->formattedPrice() }}</td>
                                <td class="px-4 py-3 text-ink">{{ $product->stock }}</td>
                                <td class="px-4 py-3 text-ink">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-4 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">Ubah</a>
                                        <form method="POST" action="{{ route('admin.products.toggle', $product) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-4 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Hapus produk ini? Produk yang sudah dipakai di pesanan tidak bisa dihapus.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-4 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
