<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold leading-tight text-ink">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-[6px] border-[1.5px] border-ink bg-card p-6">
                <p class="text-base leading-7 text-ink">
                    Halo, {{ auth()->user()->name }}. Anda masuk sebagai {{ auth()->user()->role === 'admin' ? 'admin' : 'pelanggan' }}.
                </p>
                @if (auth()->user()->isAdmin())
                    <p class="mt-2 text-sm leading-6 text-muted">
                        Kelola pesanan dan produk dari panel admin.
                    </p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2">
                            Panel admin
                        </a>
                        <a href="{{ route('admin.orders.index') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">
                            Pesanan
                        </a>
                        <a href="{{ route('admin.products.index') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">
                            Produk
                        </a>
                    </div>
                @else
                    <p class="mt-2 text-sm leading-6 text-muted">
                        Lihat riwayat pesananmu atau lanjut belanja di katalog.
                    </p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('orders.index') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-5 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2">
                            Riwayat pesanan
                        </a>
                        <a href="{{ route('catalog.index') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-5 text-sm font-bold text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">
                            Buka katalog
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
