<x-app-layout>
    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold leading-8 text-ink">Ubah produk</h1>
        <p class="mt-1 text-base leading-6 text-muted">{{ $product->name }}</p>

        @include('partials.flash')

        <form method="POST" action="{{ route('admin.products.update', $product) }}" class="mt-5 space-y-4 rounded-[6px] border-[1.5px] border-ink bg-card p-4 sm:p-5">
            @csrf
            @method('PUT')
            @include('admin.products._form')
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2">Simpan perubahan</button>
                <a href="{{ route('admin.products.index') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">Kembali ke daftar produk</a>
            </div>
        </form>
    </div>
</x-app-layout>
