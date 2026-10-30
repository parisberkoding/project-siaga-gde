<?php

use App\Providers\AppServiceProvider;

/**
 * BUG-SEC-004 — sanitasi pesan exception tidak ada artinya bila debug
 * menyala: Laravel akan tetap menampilkan stack trace/SQL ke pengguna.
 * Guard di AppServiceProvider::boot() memaksa debug mati di produksi.
 */
it('mematikan debug di lingkungan production walau APP_DEBUG bernilai true', function () {
    $app = app();
    $semula = $app->environment();

    try {
        $app->detectEnvironment(fn () => 'production');
        config(['app.debug' => true]);

        (new AppServiceProvider($app))->boot();

        expect($app->environment())->toBe('production')
            ->and(config('app.debug'))->toBeFalse();
    } finally {
        $app->detectEnvironment(fn () => $semula);
    }
});

it('tidak mengubah debug di luar production agar alur debug lokal tetap jalan', function () {
    $app = app();
    $semula = $app->environment();

    try {
        $app->detectEnvironment(fn () => 'local');
        config(['app.debug' => true]);

        (new AppServiceProvider($app))->boot();

        expect(config('app.debug'))->toBeTrue();
    } finally {
        $app->detectEnvironment(fn () => $semula);
        config(['app.debug' => false]);
    }
});

it('menyimpan APP_DEBUG=false sebagai default pada .env.example', function () {
    $isi = file_get_contents(base_path('.env.example'));

    expect($isi)->toMatch('/^APP_DEBUG=false$/m');
});
