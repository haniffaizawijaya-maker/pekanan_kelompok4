<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Hanya user dengan role admin yang boleh lewat. Tamu sudah
     * diarahkan ke login oleh middleware auth yang berjalan
     * lebih dulu di grup rute /admin; di sini bukan admin
     * mendapat 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin() ?? false, 403);

        return $next($request);
    }
}
