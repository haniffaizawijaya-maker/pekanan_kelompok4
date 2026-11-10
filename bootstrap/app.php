<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias untuk gate admin (lihat EnsureAdmin): dipakai
        // grup rute /admin, setelah middleware auth.
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);

        // Callback Tripay dipanggil server ke server oleh Tripay
        // dan diamankan dengan verifikasi signature dari raw body
        // (lihat TripayCallbackController), jadi dikecualikan dari
        // CSRF seperti webhook pada umumnya.
        $middleware->validateCsrfTokens(except: [
            'tripay/callback',
        ]);

        // B2: trusted proxy hanya bila operator memilihnya lewat
        // variabel environment TRUSTED_PROXIES (misalnya '*'
        // untuk reverse proxy di depan server ini, sesuai
        // TrustProxies bawaan Laravel 12). Tanpa variabel ini,
        // tidak ada proxy yang dipercaya, jadi header
        // X-Forwarded-* dari internet tidak bisa memalsukan
        // skema atau IP. Setel sebagai variabel environment
        // server bila config di-cache, karena .env tidak dibaca
        // saat config sudah di-cache.
        $trustedProxies = env('TRUSTED_PROXIES');

        if (is_string($trustedProxies) && $trustedProxies !== '') {
            $middleware->trustProxies(at: $trustedProxies);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
