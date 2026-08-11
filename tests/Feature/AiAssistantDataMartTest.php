<?php

use App\Models\AiChatMessage;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->admin = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    $this->admin->roles()->attach($role->id, ['is_primary' => true]);

    config(['ai.datamart.enabled' => true]);
    config(['ai.datamart.cache_ttl' => 0]);
    Cache::flush();
});

function fakeGeminiText(string $text): void
{
    Http::fake([
        '*' => Http::response([
            'candidates' => [
                ['content' => ['role' => 'model', 'parts' => [['text' => $text]]]],
            ],
        ], 200),
    ]);
}

it('menjawab pertanyaan agregasi lewat jalur Data Mart', function () {
    fakeGeminiText('Total pengeluaran BBM bulan ini adalah Rp 600.000.');

    $requester = User::factory()->create(['nama_lengkap' => 'Fajar']);
    Permohonan::factory()->count(3)->create([
        'requester_id' => $requester->id,
        'status' => 'completed',
        'bill_amounts' => 200000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), [
            'message' => 'Berapa total pengeluaran BBM bulan ini?',
        ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('reply', 'Total pengeluaran BBM bulan ini adalah Rp 600.000.');

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['module'])->toBe('permohonan_bbm')
        ->and($assistant->metadata['intent'])->toBe('aggregation')
        ->and($assistant->metadata['records_matched'])->toBe(3)
        ->and($assistant->metadata['records_injected'])->toBe(0)
        ->and($assistant->metadata['aggregation'])->toBe('SUM')
        ->and($assistant->metadata['source'])->toBe('database')
        ->and($assistant->metadata)->toHaveKey('semantic_query');

    Http::assertSent(fn ($request) => str_contains($request->body(), 'KONTEKS DATA TERVERIFIKASI'));
});

it('mengirim konteks terverifikasi berisi angka hasil backend', function () {
    fakeGeminiText('ok');

    $requester = User::factory()->create(['nama_lengkap' => 'Gita']);
    Permohonan::factory()->create([
        'requester_id' => $requester->id,
        'status' => 'completed',
        'bill_amounts' => 750000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Berapa total pengeluaran BBM bulan ini?'])
        ->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->body(), 'Rp 750.000'));
});

it('pertanyaan detail mengirim top-k records dengan flag truncated', function () {
    fakeGeminiText('Ini transaksi terbesar:');

    $requester = User::factory()->create(['nama_lengkap' => 'Hana']);
    Permohonan::factory()->create(['requester_id' => $requester->id, 'bill_amounts' => 900000, 'status' => 'approved']);
    Permohonan::factory()->create(['requester_id' => $requester->id, 'bill_amounts' => 100000, 'status' => 'approved']);

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Tampilkan 1 transaksi BBM terbesar'])
        ->assertOk();

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['intent'])->toBe('detail')
        ->and($assistant->metadata['records_injected'])->toBe(1)
        ->and($assistant->metadata['truncated'])->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->body(), 'TRUNCATED: true'));
});

it('mendukung pertanyaan lanjutan (follow-up) dengan konteks sesi', function () {
    fakeGeminiText('Jawaban pertama');

    $requester = User::factory()->create(['nama_lengkap' => 'Indra']);
    Permohonan::factory()->count(2)->create([
        'requester_id' => $requester->id,
        'status' => 'completed',
        'bill_amounts' => 300000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);

    $first = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Berapa total pengeluaran BBM bulan ini?'])
        ->assertOk()
        ->json();

    fakeGeminiText('Jawaban lanjutan');

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), [
            'message' => 'Kalau Pertamax saja?',
            'session_id' => $first['session_id'],
        ])
        ->assertOk();

    $followUp = AiChatMessage::where('role', 'assistant')->latest('id')->first();
    expect($followUp->metadata['module'])->toBe('permohonan_bbm')
        ->and($followUp->metadata['module_reused'])->toBeTrue()
        ->and($followUp->metadata['filters']['gasoline_type'])->toBe('Pertamax')
        ->and($followUp->metadata['filters'])->toHaveKey('date_from');

    Http::assertSent(fn ($request) => str_contains($request->body(), 'gasoline_type=Pertamax'));
});

// MARKER_NEXT_TESTS

it('melaporkan data kosong secara jujur', function () {
    fakeGeminiText('Tidak ditemukan data pengeluaran pada periode ini.');

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Berapa total pengeluaran BBM bulan ini?'])
        ->assertOk();

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['records_matched'])->toBe(0);

    Http::assertSent(fn ($request) => str_contains($request->body(), 'RECORD COUNT: 0'));
});

it('fallback ke function calling saat module tidak dapat ditentukan', function () {
    Http::fake([
        '*' => Http::response([
            'candidates' => [
                ['content' => ['role' => 'model', 'parts' => [['text' => 'Halo! Saya AI Assistant.']]]],
            ],
        ], 200),
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Halo, apa kabar?'])
        ->assertOk()
        ->assertJsonPath('reply', 'Halo! Saya AI Assistant.');

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['fallback'])->toBe('function_calling');
});

it('menangani kegagalan Gemini dengan pesan aman tanpa karangan', function () {
    Http::fake(['*' => Http::response('Internal Server Error', 500)]);

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Berapa total pengeluaran BBM bulan ini?'])
        ->assertOk()
        ->assertJsonPath('reply', '⚠️ Gagal terhubung ke Gemini. Periksa koneksi / API key, lalu coba lagi.');
});

it('tidak menyentuh API saat Data Mart tidak dikonfigurasi', function () {
    config(['services.gemini.api_key' => null]);
    Http::fake();

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Halo'])
        ->assertOk();

    Http::assertNothingSent();
});
