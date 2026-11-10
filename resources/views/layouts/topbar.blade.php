<header class="flex min-h-[64px] flex-wrap items-center gap-3 border-b-[1.5px] border-ink bg-mustard px-3 py-3 sm:flex-nowrap sm:px-4 sm:py-0">
    <button type="button" id="tombol-menu-seluler" aria-expanded="false" aria-controls="panel-menu-seluler" aria-label="Buka menu" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card text-ink focus:outline-none focus:ring-2 focus:ring-ink md:hidden">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M4 7h16M4 12h16M4 17h16" />
        </svg>
    </button>

    <a href="{{ route('catalog.index') }}" class="inline-flex min-h-[44px] shrink-0 items-center text-base font-bold text-ink focus:outline-none focus:ring-2 focus:ring-ink md:hidden">{{ config('app.name', 'Sudoy') }}</a>

    <form method="GET" action="{{ route('catalog.index') }}" role="search" class="order-3 flex basis-full items-center gap-2 sm:order-none sm:min-w-0 sm:max-w-xl sm:flex-1 sm:basis-auto">
        <label for="cari-topbar" class="sr-only">Cari produk</label>
        <div class="relative min-w-0 flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
            </svg>
            <input id="cari-topbar" type="search" name="q" value="{{ request('q') }}" placeholder="Cari Netflix, Canva, atau lainnya" class="h-11 w-full rounded-[6px] border-[1.5px] border-ink bg-card pl-10 pr-3 text-base text-ink placeholder:text-muted focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink">
        </div>
        <button type="submit" class="inline-flex h-11 shrink-0 items-center rounded-[6px] border-[1.5px] border-ink bg-ink px-4 text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2">Cari</button>
    </form>

    <div class="ml-auto flex shrink-0 items-center gap-3">
        @auth
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button type="button" class="inline-flex min-h-[44px] items-center gap-2 rounded-[6px] border-[1.5px] border-ink bg-card px-2 py-1 text-ink focus:outline-none focus:ring-2 focus:ring-ink" aria-label="Menu akun {{ Auth::user()->name }}">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-[1.5px] border-ink bg-parchment text-sm font-bold text-ink" aria-hidden="true">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                        <span class="hidden text-left sm:block">
                            <span class="block max-w-[140px] truncate text-sm font-bold leading-4">{{ Auth::user()->name }}</span>
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-muted">{{ Auth::user()->role === 'admin' ? 'Admin' : 'Pelanggan' }}</span>
                        </span>
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>
                    <x-dropdown-link :href="route('dashboard')">Dashboard</x-dropdown-link>
                    <x-dropdown-link :href="route('orders.index')">Pesanan Saya</x-dropdown-link>
                    @if (Auth::user()->isAdmin())
                        <x-dropdown-link :href="route('admin.dashboard')">Panel Admin</x-dropdown-link>
                        <x-dropdown-link :href="route('admin.products.index')">Produk</x-dropdown-link>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Keluar</x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        @else
            <span class="hidden items-center gap-2 sm:inline-flex">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border-[1.5px] border-ink bg-card text-sm font-bold text-ink" aria-hidden="true">?</span>
                <span class="text-left">
                    <span class="block text-sm font-bold leading-4">Tamu</span>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-muted">Belum masuk</span>
                </span>
            </span>
            <a href="{{ route('login') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-4 text-sm font-bold text-ink focus:outline-none focus:ring-2 focus:ring-ink">Masuk</a>
            <a href="{{ route('register') }}" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-accent px-4 text-sm font-bold text-white shadow-[2px_2px_0_#191919] hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink">Daftar</a>
        @endauth
    </div>
</header>
