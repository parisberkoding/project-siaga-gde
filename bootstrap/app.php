<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cloudflare Quick Tunnel berjalan di mesin yang sama dan meneruskan
        // request lewat 127.0.0.1. Tanpa trusted proxy, Laravel melihat skema
        // HTTP sehingga URL/redirect yang dihasilkan menjadi http://....
        // Percayai HANYA loopback, bukan semua proxy.
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'central' => \App\Http\Middleware\EnsureCentralUnit::class,
            'lunch.vendor_guard' => \App\Http\Middleware\RestrictLunchVendor::class,
            'feature' => \App\Http\Middleware\EnsureFeatureIsProduction::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            ]);

        $middleware->web(append: [
            \App\Http\Middleware\VerifyDeviceFingerprint::class,
            // Nonaktifkan akun harus langsung berlaku, tidak menunggu sesi
            // kedaluwarsa. Ditempatkan setelah fingerprint supaya sidik jari
            // tidak diset untuk sesi yang akan segera diputus.
            \App\Http\Middleware\EnsureAccountIsActive::class,
            // Isolasi modul: setelah user memilih modul di halaman All Modules,
            // route milik modul lain ditolak sampai user kembali ke All Modules.
            // Ditempatkan setelah pengecekan akun agar user nonaktif tidak
            // mendapat redirect modul terlebih dahulu.
            \App\Http\Middleware\EnsureModuleContext::class,
            \Spatie\Csp\AddCspHeaders::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            // Cetak thermal via window.print() tetap butuh POST tanpa token dari halaman terpisah.
            'api/thermer-print',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

    })->create();
