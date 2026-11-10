@if ($errors->any())
    <div class="rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm leading-6 text-ink" role="alert">
        <p class="font-bold">Periksa isian berikut:</p>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div>
    <label for="name" class="block text-[11px] font-bold uppercase tracking-wider text-muted">Nama produk</label>
    <input id="name" type="text" name="name" value="{{ old('name', $product->name) }}" required maxlength="255" class="mt-1 h-11 w-full rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink">
</div>

<div>
    <label for="category_id" class="block text-[11px] font-bold uppercase tracking-wider text-muted">Kategori</label>
    <select id="category_id" name="category_id" required class="mt-1 h-11 w-full rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink">
        <option value="">Pilih kategori</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="description" class="block text-[11px] font-bold uppercase tracking-wider text-muted">Deskripsi</label>
    <textarea id="description" name="description" required rows="4" maxlength="5000" class="mt-1 w-full rounded-[6px] border-[1.5px] border-ink bg-card px-3 py-2 text-base leading-6 text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink">{{ old('description', $product->description) }}</textarea>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label for="price" class="block text-[11px] font-bold uppercase tracking-wider text-muted">Harga (rupiah)</label>
        <input id="price" type="number" name="price" value="{{ old('price', $product->price) }}" required min="0" max="100000000" step="1" class="mt-1 h-11 w-full rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink">
    </div>
    <div>
        <label for="stock" class="block text-[11px] font-bold uppercase tracking-wider text-muted">Stok</label>
        <input id="stock" type="number" name="stock" value="{{ old('stock', $product->stock) }}" required min="0" max="100000" step="1" class="mt-1 h-11 w-full rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink">
    </div>
</div>

<div>
    <label for="image_url" class="block text-[11px] font-bold uppercase tracking-wider text-muted">URL gambar atau path lokal (boleh kosong)</label>
    <input id="image_url" type="text" name="image_url" value="{{ old('image_url', $product->image_url) }}" maxlength="2048" placeholder="https://... atau images/products/nama.jpg" class="mt-1 h-11 w-full rounded-[6px] border-[1.5px] border-ink bg-card px-3 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink">
    <p class="mt-1 text-sm leading-6 text-muted">Isi URL lengkap (https://...) atau path lokal di bawah folder images/ atau storage/. Kosongkan bila tidak ada gambar; tampilannya jatuh ke mark play.</p>
</div>

<label class="flex min-h-[44px] cursor-pointer items-center gap-3">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="h-5 w-5 shrink-0 accent-accent">
    <span class="text-base text-ink">Aktif dan tampil di katalog</span>
</label>

@if ($product->exists)
    <p class="text-sm leading-6 text-muted">Slug: {{ $product->slug }}. Slug dibuat ulang otomatis dari nama saat disimpan.</p>
@endif
