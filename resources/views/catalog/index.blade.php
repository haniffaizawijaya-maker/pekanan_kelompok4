<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b-[1.5px] border-ink/30 pb-3">
            <span class="text-xs font-bold uppercase tracking-widest text-accent">● SYSTEM ARCHIVE // DIGITAL SUBSCRIPTIONS</span>
            <span class="text-xs font-bold uppercase tracking-widest text-muted">REV 2047.04 / SUDOY MK.I</span>
        </div>
        <h1 class="text-3xl font-bold leading-9 text-ink">Katalog produk</h1>
        <p class="mt-1 text-base leading-6 text-muted">Langganan premium digital dari Sudoy.</p>

        <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm leading-6 text-ink" role="note">
            Katalog contoh untuk latihan. Produk di halaman ini data dummy lokal, bukan akun sungguhan dan tidak dijual.
        </div>

        <ol class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Cara berbelanja">
            <li class="flex items-center gap-3 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-parchment text-sm font-bold text-accent" aria-hidden="true">01</span>
                <span class="text-sm font-bold leading-6 text-ink">Pilih produk</span>
            </li>
            <li class="flex items-center gap-3 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-parchment text-sm font-bold text-accent" aria-hidden="true">02</span>
                <span class="text-sm font-bold leading-6 text-ink">Isi nama dan email</span>
            </li>
            <li class="flex items-center gap-3 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-parchment text-sm font-bold text-accent" aria-hidden="true">03</span>
                <span class="text-sm font-bold leading-6 text-ink">Scan QRIS</span>
            </li>
            <li class="flex items-center gap-3 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-parchment text-sm font-bold text-accent" aria-hidden="true">04</span>
                <span class="text-sm font-bold leading-6 text-ink">Pantau di Pesanan Saya</span>
            </li>
        </ol>

        <div class="mt-5">
            <livewire:product-list />
        </div>
    </div>
</x-app-layout>
