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
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'central' => \App\Http\Middleware\EnsureCentralUnit::class,
            'feature' => \App\Http\Middleware\EnsureFeatureIsProduction::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            ]);

        $middleware->web(append: [
            \App\Http\Middleware\VerifyDeviceFingerprint::class,
            // Nonaktifkan akun harus langsung berlaku, tidak menunggu sesi
            // kedaluwarsa. Ditempatkan setelah fingerprint supaya sidik jari
            // tidak diset untuk sesi yang akan segera diputus.
            \App\Http\Middleware\EnsureAccountIsActive::class,
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
