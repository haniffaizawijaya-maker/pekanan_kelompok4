<aside class="hidden w-[88px] shrink-0 flex-col border-r-[1.5px] border-ink bg-parchment md:flex" aria-label="Navigasi utama">
    <a href="{{ route('catalog.index') }}" class="flex min-h-[64px] flex-col items-center justify-center gap-1 border-b-[1.5px] border-ink bg-mustard px-1 py-2 text-center text-ink focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ink" aria-label="{{ config('app.name', 'Sudoy') }}, ke katalog">
        <svg class="h-7 w-7" viewBox="0 0 48 48" fill="none" aria-hidden="true">
            <rect x="8" y="8" width="32" height="32" rx="6" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" />
            <path d="M20 17.5 33 24l-13 6.5v-13Z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" />
            <path d="M8 42h28" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
        </svg>
        <span class="text-[11px] font-bold leading-none">{{ config('app.name', 'Sudoy') }}</span>
    </a>

    <nav class="flex flex-1 flex-col">
        <a href="{{ route('catalog.index') }}" class="flex min-h-[64px] flex-col items-center justify-center gap-1 border-b border-ink/20 px-1 py-2 text-center text-ink focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ink {{ request()->routeIs('catalog.index') || request()->routeIs('products.show') ? 'bg-mustard font-bold' : 'font-medium hover:bg-cream' }}" aria-current="{{ request()->routeIs('catalog.index') || request()->routeIs('products.show') ? 'page' : 'false' }}">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                <rect x="4" y="4" width="7" height="7" rx="1" /><rect x="13" y="4" width="7" height="7" rx="1" /><rect x="4" y="13" width="7" height="7" rx="1" /><rect x="13" y="13" width="7" height="7" rx="1" />
            </svg>
            <span class="text-[11px] leading-none">Katalog</span>
        </a>

        @auth
            <div class="border-b border-ink/20 {{ request()->routeIs('cart.index') ? 'bg-mustard' : '' }}">
                <livewire:cart-counter variant="sidebar" key="cart-counter-sidebar" />
            </div>

            <a href="{{ route('dashboard') }}" class="flex min-h-[64px] flex-col items-center justify-center gap-1 border-b border-ink/20 px-1 py-2 text-center text-ink focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ink {{ request()->routeIs('dashboard') ? 'bg-mustard font-bold' : 'font-medium hover:bg-cream' }}" aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                    <rect x="4" y="4" width="16" height="16" rx="1" /><path d="M4 10h16M10 10v10" />
                </svg>
                <span class="text-[11px] leading-none">Dashboard</span>
            </a>

            <a href="{{ route('orders.index') }}" class="flex min-h-[64px] flex-col items-center justify-center gap-1 border-b border-ink/20 px-1 py-2 text-center text-ink focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ink {{ request()->routeIs('orders.index') || request()->routeIs('orders.show') || request()->routeIs('orders.pay') ? 'bg-mustard font-bold' : 'font-medium hover:bg-cream' }}" aria-current="{{ request()->routeIs('orders.index') || request()->routeIs('orders.show') || request()->routeIs('orders.pay') ? 'page' : 'false' }}">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                    <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z" /><path d="M9 8h6M9 12h6" />
                </svg>
                <span class="text-[11px] leading-none">Pesanan</span>
            </a>

            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="flex min-h-[64px] flex-col items-center justify-center gap-1 border-b border-ink/20 px-1 py-2 text-center text-ink focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ink {{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.orders.*') ? 'bg-mustard font-bold' : 'font-medium hover:bg-cream' }}" aria-current="{{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.orders.*') ? 'page' : 'false' }}">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                        <path d="M4 5h16M4 12h16M4 19h16" /><circle cx="9" cy="5" r="2" fill="currentColor" stroke="none" /><circle cx="15" cy="12" r="2" fill="currentColor" stroke="none" /><circle cx="9" cy="19" r="2" fill="currentColor" stroke="none" />
                    </svg>
                    <span class="text-[11px] leading-none">Panel Admin</span>
                </a>

                <a href="{{ route('admin.products.index') }}" class="flex min-h-[64px] flex-col items-center justify-center gap-1 border-b border-ink/20 px-1 py-2 text-center text-ink focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ink {{ request()->routeIs('admin.products.*') ? 'bg-mustard font-bold' : 'font-medium hover:bg-cream' }}" aria-current="{{ request()->routeIs('admin.products.*') ? 'page' : 'false' }}">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 7 12 3l8 4v10l-8 4-8-4V7Z" /><path d="M4 7l8 4 8-4M12 11v10" />
                    </svg>
                    <span class="text-[11px] leading-none">Produk</span>
                </a>
            @endif

            <a href="{{ route('profile.edit') }}" class="flex min-h-[64px] flex-col items-center justify-center gap-1 border-b border-ink/20 px-1 py-2 text-center text-ink focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ink {{ request()->routeIs('profile.edit') ? 'bg-mustard font-bold' : 'font-medium hover:bg-cream' }}" aria-current="{{ request()->routeIs('profile.edit') ? 'page' : 'false' }}">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="4" /><path d="M4 20c1.5-3.5 4.5-5 8-5s6.5 1.5 8 5" />
                </svg>
                <span class="text-[11px] leading-none">Profil</span>
            </a>
        @else
            <a href="{{ route('login') }}" class="flex min-h-[64px] flex-col items-center justify-center gap-1 border-b border-ink/20 px-1 py-2 text-center text-ink focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ink {{ request()->routeIs('login') ? 'bg-mustard font-bold' : 'font-medium hover:bg-cream' }}">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 4h6v16h-6M10 8l4 4-4 4M14 12H4" />
                </svg>
                <span class="text-[11px] leading-none">Masuk</span>
            </a>

            <a href="{{ route('register') }}" class="flex min-h-[64px] flex-col items-center justify-center gap-1 border-b border-ink/20 px-1 py-2 text-center text-ink focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ink {{ request()->routeIs('register') ? 'bg-mustard font-bold' : 'font-medium hover:bg-cream' }}">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                <span class="text-[11px] leading-none">Daftar</span>
            </a>
        @endauth
    </nav>

    @auth
        <form method="POST" action="{{ route('logout') }}" class="mt-auto border-t-[1.5px] border-ink">
            @csrf
            <button type="submit" class="flex min-h-[64px] w-full flex-col items-center justify-center gap-1 px-1 py-2 text-center font-medium text-ink hover:bg-cream focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ink">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10 4H4v16h6M14 8l-4 4 4 4M10 12h10" />
                </svg>
                <span class="text-[11px] leading-none">Keluar</span>
            </button>
        </form>
    @endauth
</aside>
