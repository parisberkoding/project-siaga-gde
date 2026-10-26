<?php

use App\Models\User;
use App\Services\GeminiStatusService;
use App\Services\ImageAnalysisService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

// Regresi: deteksi "Gemini Reach Limit" + fallback form manual.
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config([
        'imageanalysis.api_key'            => 'test-key',
        'imageanalysis.base_url'           => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
        'imageanalysis.fallback_enabled'   => true,
        'imageanalysis.rate_limit_flag_ttl' => 60,
    ]);
    GeminiStatusService::markHealthy();
});

function strukPalsu(): string
{
    // File fisik nyata (konten tak relevan — Http::fake mencegah request keluar).
    $path = tempnam(sys_get_temp_dir(), 'struk') . '.png';
    imagepng(imagecreatetruecolor(60, 80), $path);

    return $path;
}

it('mengembalikan status api_limit saat Gemini 429 dan mengaktifkan flag', function () {
    Http::fake(['*' => Http::response(['error' => ['status' => 'RESOURCE_EXHAUSTED']], 429)]);

    $result = app(ImageAnalysisService::class)->analyzeImage(strukPalsu());

    expect($result['status'])->toBe('api_limit')
        ->and($result['kategori'])->toBe('Lainnya')
        ->and(GeminiStatusService::isActive())->toBeTrue();
});

it('mendeteksi quota exhausted meski http 403', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'quota exceeded']], 403)]);

    $result = app(ImageAnalysisService::class)->analyzeImage(strukPalsu());

    expect($result['status'])->toBe('api_limit')
        ->and(GeminiStatusService::isActive())->toBeTrue();
});

it('menghapus flag rate-limit ketika analisis berhasil lagi', function () {
    GeminiStatusService::markRateLimited();

    Http::fake(['*' => Http::response([
        'candidates' => [[
            'content' => ['parts' => [[
                'text' => json_encode([
                    'kategori' => 'BBM',
                    'metadata' => ['tanggal' => '2026-08-20', 'nominal' => 100000, 'volume' => 10],
                    'text'     => 'STRUK SPBU',
                ]),
            ]]],
        ]],
    ], 200)]);

    $result = app(ImageAnalysisService::class)->analyzeImage(strukPalsu());

    expect($result['status'])->toBe('ok')
        ->and($result['kategori'])->toBe('BBM')
        ->and(GeminiStatusService::isActive())->toBeFalse();
});

it('mengembalikan status api_error utk respons tidak terparse', function () {
    Http::fake(['*' => Http::response(['candidates' => [[
        'content' => ['parts' => [['text' => 'bukan json']]],
    ]]], 200)]);

    $result = app(ImageAnalysisService::class)->analyzeImage(strukPalsu());

    expect($result['status'])->toBe('api_error');
});

it('meneruskan status & has_api_limit pada endpoint analyze-struk', function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $admin = User::factory()->create(['role' => 'superadmin']);
    $role = \App\Models\Role::where('code', 'superadmin')->first();
    if ($role) {
        $admin->roles()->attach($role->id, ['is_primary' => true]);
    }

    Http::fake(['*' => Http::response(['error' => ['status' => 'RESOURCE_EXHAUSTED']], 429)]);

    $this->actingAs($admin)
        ->post(route('pelaporan.analyze-struk'), [
            'images' => [UploadedFile::fake()->image('a.png'), UploadedFile::fake()->image('b.png')],
        ])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('has_api_limit', true)
        ->assertJsonPath('results.0.status', 'api_limit');

    expect(GeminiStatusService::isActive())->toBeTrue();
});

it('menampilkan banner gemini utk superadmin saat limit aktif', function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $admin = User::factory()->create(['role' => 'superadmin']);
    GeminiStatusService::markRateLimited();

    $this->actingAs($admin);
    $html = view('partials.gemini-status-banner')->render();

    expect($html)->toContain('mencapai batas kuota');
});

it('menyembunyikan banner gemini dari requester', function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $requester = User::factory()->create(['role' => 'requester']);
    GeminiStatusService::markRateLimited();

    $this->actingAs($requester);
    $html = view('partials.gemini-status-banner')->render();

    expect($html)->not->toContain('mencapai batas kuota');
});
