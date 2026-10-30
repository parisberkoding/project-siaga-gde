<?php

use App\Services\GasolinePriceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

/**
 * BUG-SEC-004 — pesan exception mentah (SQL/trace) tidak boleh bocor ke
 * pengguna. Detail teknis dicatat ke log internal.
 */
uses(RefreshDatabase::class);

it('helper mengembalikan pesan ramah dan mencatat detail exception ke log', function () {
    Log::spy();

    $pesan = safe_exception_message(
        new RuntimeException('SQLSTATE[HY000]: rahasia_kolom_voucher tidak ditemukan'),
        'Gagal menyimpan data.'
    );

    expect($pesan)->toBe('Gagal menyimpan data.')
        ->and($pesan)->not->toContain('SQLSTATE')
        ->and($pesan)->not->toContain('rahasia_kolom_voucher');

    Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
        return str_contains($message, 'rahasia_kolom_voucher')
            && isset($context['exception']);
    })->once();
});

it('kegagalan sinkronisasi harga BBM menampilkan pesan ramah, bukan SQL', function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $admin = \App\Models\User::factory()->create(['role' => 'general_affairs']);
    $admin->roles()->attach(
        \App\Models\Role::where('code', 'general_affairs')->firstOrFail()->id,
        ['is_primary' => true]
    );

    Log::spy();

    $this->mock(GasolinePriceSyncService::class, function ($mock) {
        $mock->shouldReceive('sync')->once()->andThrow(
            new RuntimeException('SQLSTATE[42S02]: tabel rahasia_vouchers tidak ada')
        );
    });

    $response = $this->actingAs($admin)->post(route('master-gasoline.sync'));

    $response->assertRedirect();
    $response->assertSessionHas('error');

    expect(session('error'))
        ->toContain('Gagal sinkronisasi harga BBM')
        ->not->toContain('SQLSTATE')
        ->not->toContain('rahasia_vouchers');
});

it('tidak ada controller yang membocorkan pesan exception mentah ke pengguna', function () {
    $files = collect(glob(app_path('Http/Controllers/*.php')))
        ->merge(glob(app_path('Http/Controllers/*/*.php')));

    $pelanggaran = [];

    foreach ($files as $file) {
        // SsoController hanya meneruskan SsoException — pesan domain yang
        // memang dirancang untuk ditampilkan (bukan SQL/trace internal).
        if (basename($file) === 'SsoController.php') {
            continue;
        }

        foreach (file($file) as $index => $line) {
            $trim = ltrim($line);

            // Abaikan komentar dan pesan domain yang memang dirancang ramah.
            if (str_starts_with($trim, '//') || str_starts_with($trim, '*')) {
                continue;
            }

            if (! str_contains($line, 'getMessage()') || str_contains($line, 'SsoException')) {
                continue;
            }

            $userFacing = preg_match(
                '/(->with\(\s*[\'"]error[\'"]|[\'"](?:message|error)[\'"]\s*=>)/',
                $line
            );

            if ($userFacing) {
                $pelanggaran[] = basename($file).':'.($index + 1).' '.trim($line);
            }
        }
    }

    expect($pelanggaran)->toBe([]);
});
