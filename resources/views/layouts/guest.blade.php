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
    <body class="font-sans text-ink antialiased">
        <div class="flex min-h-screen flex-col items-center bg-beige px-4 py-6 sm:justify-center sm:py-10">
            <a href="/" class="flex items-center gap-3 focus:outline-none focus:ring-2 focus:ring-ink" aria-label="{{ config('app.name', 'Sudoy') }}, ke katalog">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-mustard text-ink">
                    <x-application-logo class="h-7 w-7" />
                </span>
                <span class="font-sans text-2xl font-bold leading-none text-ink">{{ config('app.name', 'Sudoy') }}</span>
            </a>

            <div class="mt-6 w-full overflow-hidden rounded-[6px] border-[1.5px] border-ink bg-cream sm:max-w-md">
                <div class="h-3 border-b-[1.5px] border-ink bg-mustard" aria-hidden="true"></div>
                <div class="px-6 py-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
