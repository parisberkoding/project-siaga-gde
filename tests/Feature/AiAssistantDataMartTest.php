<?php

use App\Models\AiChatMessage;
use App\Models\LpjPenggantian;
use App\Models\LunchDistribution;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\Site;
use App\Models\SuratSpd;
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

function fakeLlmText(string $text): void
{
    Http::fake([
        '*' => Http::response([
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => $text]],
            ],
        ], 200),
    ]);
}

it('menjawab pertanyaan agregasi lewat jalur Data Mart', function () {
    fakeLlmText('Total pengeluaran BBM bulan ini adalah Rp 600.000.');

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
    fakeLlmText('ok');

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
    fakeLlmText('Ini transaksi terbesar:');

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
    fakeLlmText('Jawaban pertama');

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

    fakeLlmText('Jawaban lanjutan');

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

it('menjawab jumlah surat SPD via jalur Data Mart', function () {
    fakeLlmText('Ada 2 surat SPD yang sudah diterbitkan.');

    $user = User::factory()->create(['nama_lengkap' => 'Bima']);
    SuratSpd::factory()->count(2)->create([
        'user_id' => $user->id,
        'status' => 'approved',
        'tanggal_berangkat' => now()->subDays(5),
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'ada berapa banyak surat SPD yang keluar?'])
        ->assertOk();

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['module'])->toBe('spd')
        ->and($assistant->metadata['aggregation'])->toBe('COUNT')
        ->and($assistant->metadata['records_matched'])->toBe(2);
});

it('menjawab LPJ yang telat dengan filter is_late', function () {
    fakeLlmText('Ada 1 LPJ yang telat.');

    $user = User::factory()->create(['nama_lengkap' => 'Citra']);
    $spd = SuratSpd::factory()->create([
        'user_id' => $user->id,
        'status' => 'approved',
        'tanggal_berangkat' => now()->subDays(20),
        'tanggal_kembali' => now()->subDays(16),
        'lpj_deadline' => now()->subDays(13),
    ]);
    LpjPenggantian::create([
        'lpj_number' => 'PTH.01/LPJ/SPD/2026/08/0001',
        'slug' => 'PTH.01-LPJ-SPD-2026-08-0001',
        'surat_spd_id' => $spd->id,
        'user_id' => $user->id,
        'nama' => 'Citra',
        'jabatan' => 'Staff',
        'vehicle_id' => $spd->vehicle_id,
        'nomor_polisi' => $spd->vehicle?->consumerial_code,
        'uraian' => 'Perjalanan dinas',
        'tanggal_lpj' => now()->subDays(10),
        'is_late' => true,
        'days_late' => 3,
        'total_pengeluaran' => 500000,
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'berapa LPJ yang telat?'])
        ->assertOk();

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['module'])->toBe('lpj')
        ->and($assistant->metadata['filters']['is_late'])->toBeTrue()
        ->and($assistant->metadata['records_matched'])->toBe(1);
});

it('menjawab jumlah porsi makan siang via jalur Data Mart', function () {
    fakeLlmText('Total porsi makan siang besok adalah 120.');

    $user = User::factory()->create(['nama_lengkap' => 'Admin']);
    Site::create(['name' => 'Patuha', 'code' => 'PTH']);
    LunchDistribution::create([
        'distribution_date' => now()->addDay()->toDateString(),
        'site_id' => Site::where('code', 'PTH')->first()->id,
        'employee_count' => 100,
        'guest_count' => 20,
        'total_count' => 120,
        'is_locked' => false,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'berapa porsi makan siang besok?'])
        ->assertOk();

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['module'])->toBe('lunch')
        ->and($assistant->metadata['aggregation'])->toBe('COUNT')
        ->and($assistant->metadata['records_matched'])->toBe(1);
});

it('melaporkan data kosong secara jujur', function () {
    fakeLlmText('Tidak ditemukan data pengeluaran pada periode ini.');

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
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => 'Halo! Saya AI Assistant.']],
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

it('menangani kegagalan LLM dengan pesan aman tanpa karangan', function () {
    Http::fake(['*' => Http::response('Internal Server Error', 500)]);

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Berapa total pengeluaran BBM bulan ini?'])
        ->assertOk()
        ->assertJsonPath('reply', '⚠️ Gagal terhubung ke AI Assistant (Groq). Periksa koneksi / API key, lalu coba lagi.');
});

it('tidak menyentuh API saat AI tidak dikonfigurasi', function () {
    config(['services.groq.api_key' => null]);
    Http::fake();

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Halo'])
        ->assertOk();

    Http::assertNothingSent();
});
