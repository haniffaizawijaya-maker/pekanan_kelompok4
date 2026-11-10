<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sudoy') }}</title>

        <!-- Fonts: Courier Prime di-host sendiri, dimuat dari CSS (lihat resources/css/app.css) -->

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased bg-beige">
        <div class="min-h-screen bg-beige p-3 sm:p-5 lg:p-7">
            <div class="mx-auto flex max-w-[1360px] border-[1.5px] border-ink/40 bg-cream shadow-2xl md:min-h-[calc(100vh-3.5rem)]">
                @include('layouts.sidebar')

                <div class="flex min-w-0 flex-1 flex-col">
                    @include('layouts.topbar')

                    <div id="panel-menu-seluler" hidden class="border-b-[1.5px] border-ink bg-cream md:hidden">
                        <nav class="py-2" aria-label="Menu seluler">
                            @auth
                                <div class="border-b border-ink/20 px-3 py-3">
                                    <p class="text-base font-bold text-ink">{{ Auth::user()->name }}</p>
                                    <p class="text-sm text-muted">{{ Auth::user()->email }}</p>
                                    <p class="mt-1 text-[11px] font-bold uppercase tracking-wider text-muted">{{ Auth::user()->role === 'admin' ? 'Admin' : 'Pelanggan' }}</p>
                                </div>
                            @endauth

                            <x-responsive-nav-link :href="route('catalog.index')" :active="request()->routeIs('catalog.index') || request()->routeIs('products.show')">Katalog</x-responsive-nav-link>
                            @auth
                                <div class="border-l-4 border-transparent">
                                    <livewire:cart-counter variant="row" key="cart-counter-mobile" />
                                </div>
                                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.index') || request()->routeIs('orders.show') || request()->routeIs('orders.pay')">Pesanan Saya</x-responsive-nav-link>
                                @if (Auth::user()->isAdmin())
                                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard') || request()->routeIs('admin.orders.*')">Panel Admin</x-responsive-nav-link>
                                    <x-responsive-nav-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">Produk</x-responsive-nav-link>
                                @endif
                                <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">Profil</x-responsive-nav-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Keluar</x-responsive-nav-link>
                                </form>
                            @else
                                <x-responsive-nav-link :href="route('login')" :active="request()->routeIs('login')">Masuk</x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('register')" :active="request()->routeIs('register')">Daftar</x-responsive-nav-link>
                            @endauth
                        </nav>
                    </div>

                    @isset($header)
                        <header class="border-b-[1.5px] border-ink bg-cream">
                            <div class="px-4 py-4 sm:px-6">
                                {{ $header }}
                            </div>
                        </header>
                    @endisset

                    <main class="min-w-0 flex-1">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
        <script>
            // Toggle menu seluler dengan JavaScript biasa, tanpa Alpine.
            // Komponen Livewire tidak boleh berada di dalam elemen x-data,
            // jadi panel ini tidak memakai x-data/x-show.
            (function () {
                var tombol = document.getElementById('tombol-menu-seluler');
                var panel = document.getElementById('panel-menu-seluler');
                if (!tombol || !panel) {
                    return;
                }
                function setTerbuka(terbuka) {
                    panel.hidden = !terbuka;
                    tombol.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
                    tombol.setAttribute('aria-label', terbuka ? 'Tutup menu' : 'Buka menu');
                }
                tombol.addEventListener('click', function () {
                    setTerbuka(panel.hidden);
                });
                panel.addEventListener('click', function (event) {
                    if (event.target.closest('a, button')) {
                        setTerbuka(false);
                    }
                });
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        setTerbuka(false);
                    }
                });
            })();
        </script>
    </body>
</html>
