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
            'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 45, 'total_tokens' => 165],
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

it('menjawab sapaan/small talk tanpa memanggil LLM', function () {
    Http::fake();

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Halo, apa kabar?'])
        ->assertOk()
        ->assertJsonPath('ok', true);

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['casual'])->toBe('greeting');

    Http::assertNothingSent();
});

it('membalas ucapan terima kasih tanpa memanggil LLM', function () {
    Http::fake();

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'oke makasih ya'])
        ->assertOk()
        ->assertJsonPath('ok', true);

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['casual'])->toBe('thanks');

    Http::assertNothingSent();
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
        ->postJson(route('ai-assistant.send'), ['message' => 'Jelaskan langkah-langkah cara pakai aplikasi ini'])
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

it('menghasilkan spec grafik saat user minta grafik by nama', function () {
    fakeLlmText('Berikut grafik pengeluaran per nama.');

    $fajar = User::factory()->create(['nama_lengkap' => 'Fajar']);
    $gita  = User::factory()->create(['nama_lengkap' => 'Gita']);
    Permohonan::factory()->create([
        'requester_id' => $fajar->id,
        'status' => 'completed',
        'bill_amounts' => 300000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);
    Permohonan::factory()->create([
        'requester_id' => $gita->id,
        'status' => 'completed',
        'bill_amounts' => 200000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Tampilkan grafik by nama dong']);

    $response->assertOk()
        ->assertJsonPath('ok', true);

    $chart = $response->json('chart');
    expect($chart)->not->toBeNull()
        ->and($chart['type'])->toBeIn(['bar', 'line', 'doughnut', 'pie', 'radar'])
        ->and($chart['labels'])->toContain('Fajar', 'Gita')
        ->and($chart['values'])->toContain(300000)
        ->and($chart['currency'])->toBeTrue();

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['chart'])->not->toBeNull()
        ->and($assistant->metadata['group_by'])->toBe('driver');
});

it('menghormati jenis grafik yang eksplisit diminta user (pie)', function () {
    fakeLlmText('Ini grafik pie-nya ya.');

    $fajar = User::factory()->create(['nama_lengkap' => 'Fajar']);
    $gita  = User::factory()->create(['nama_lengkap' => 'Gita']);
    Permohonan::factory()->create([
        'requester_id' => $fajar->id,
        'status' => 'completed',
        'bill_amounts' => 300000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);
    Permohonan::factory()->create([
        'requester_id' => $gita->id,
        'status' => 'completed',
        'bill_amounts' => 200000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Tampilkan pie chart pengeluaran per nama']);

    $response->assertOk()->assertJsonPath('ok', true);

    expect($response->json('chart'))->not->toBeNull()
        ->and($response->json('chart')['type'])->toBe('pie');
});

it('LLM dapat memilih jenis grafik via direktif CHART_TYPE', function () {
    fakeLlmText("Ini distribusi per status.\n\n[[CHART_TYPE: bar]]");

    $requester = User::factory()->create(['nama_lengkap' => 'Budi']);
    Permohonan::factory()->count(2)->create([
        'requester_id' => $requester->id,
        'status' => 'completed',
        'bill_amounts' => 200000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);
    Permohonan::factory()->count(1)->create([
        'requester_id' => $requester->id,
        'status' => 'rejected',
        'bill_amounts' => 150000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Tampilkan grafik distribusi status']);

    $response->assertOk()->assertJsonPath('ok', true);

    $chart = $response->json('chart');
    expect($chart)->not->toBeNull()
        ->and($chart['type'])->toBe('bar')
        ->and($chart['labels'])->toContain('completed', 'rejected');

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->content)->not->toContain('CHART_TYPE')
        ->and($assistant->metadata['chart']['type'])->toBe('bar');
});

it('menambahkan konteks percakapan sesi ke prompt (conversation RAG)', function () {
    fakeLlmText('Jawaban pertama');

    $requester = User::factory()->create(['nama_lengkap' => 'Joko']);
    Permohonan::factory()->create([
        'requester_id' => $requester->id,
        'status' => 'completed',
        'bill_amounts' => 450000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);

    $first = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Berapa total pengeluaran BBM bulan ini?'])
        ->assertOk()
        ->json();

    fakeLlmText('Jawaban lanjutan');

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), [
            'message' => 'Kalau dibagi per nama bagaimana?',
            'session_id' => $first['session_id'],
        ])
        ->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->body(), 'KONTEKS PERCAKAPAN SEBELUMNYA'));
});

it('mencatat token input & output pada metadata pesan dan mengembalikan total sesi', function () {
    fakeLlmText('Total pengeluaran BBM bulan ini adalah Rp 600.000.');

    $requester = User::factory()->create(['nama_lengkap' => 'Tono']);
    Permohonan::factory()->count(2)->create([
        'requester_id' => $requester->id,
        'status' => 'completed',
        'bill_amounts' => 300000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);

    $first = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Berapa total pengeluaran BBM bulan ini?'])
        ->assertOk()
        ->assertJsonPath('token_usage.input', 120)
        ->assertJsonPath('token_usage.output', 45)
        ->json();

    fakeLlmText('Jawaban lanjutan.');

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), [
            'message' => 'kalau by nama?',
            'session_id' => $first['session_id'],
        ])
        ->assertOk()
        ->assertJsonPath('token_usage.input', 240)
        ->assertJsonPath('token_usage.output', 90);

    $assistantMessages = AiChatMessage::where('role', 'assistant')->get();
    expect($assistantMessages)->toHaveCount(2)
        ->and($assistantMessages->pluck('metadata.token_input'))->each->toBe(120)
        ->and($assistantMessages->pluck('metadata.token_output'))->each->toBe(45);
});

it('tidak menghasilkan chart untuk pertanyaan tanpa grouping', function () {
    fakeLlmText('Total pengeluaran BBM bulan ini adalah Rp 600.000.');

    $requester = User::factory()->create(['nama_lengkap' => 'Rara']);
    Permohonan::factory()->count(2)->create([
        'requester_id' => $requester->id,
        'status' => 'completed',
        'bill_amounts' => 300000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Berapa total pengeluaran BBM bulan ini?'])
        ->assertOk();

    expect($response->json('chart'))->toBeNull();

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata)->not->toHaveKey('chart');
});

it('melanjutkan grouping grafik pada pertanyaan lanjutan "kalau by name?"', function () {
    fakeLlmText('Total per driver bulan ini.');

    $fajar = User::factory()->create(['nama_lengkap' => 'Fajar']);
    $gita  = User::factory()->create(['nama_lengkap' => 'Gita']);
    Permohonan::factory()->create([
        'requester_id' => $fajar->id,
        'status' => 'completed',
        'bill_amounts' => 300000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);
    Permohonan::factory()->create([
        'requester_id' => $gita->id,
        'status' => 'completed',
        'bill_amounts' => 200000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);

    $first = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Tampilkan grafik distribusi pengeluaran BBM bulan ini?'])
        ->assertOk()
        ->json();

    fakeLlmText('Berikut pengeluaran per nama.');

    $second = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), [
            'message' => 'kalau by name?',
            'session_id' => $first['session_id'],
        ])
        ->assertOk()
        ->json();

    expect($second['chart'])->not->toBeNull()
        ->and($second['chart']['labels'])->toContain('Fajar', 'Gita')
        ->and($second['chart']['values'])->toContain(300000, 200000)
        ->and($second['chart']['currency'])->toBeTrue();
});

it('menghasilkan chart baru saat user pindah modul dalam sesi yang sama', function () {
    fakeLlmText('Ringkasan SPD berhasil.');

    $spdDriver = User::factory()->create(['nama_lengkap' => 'Doni SPD']);
    SuratSpd::factory()->create([
        'user_id' => $spdDriver->id,
        'status' => 'completed',
        'total_budget' => 1000000,
    ]);

    $first = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'grafik spd'])
        ->assertOk()
        ->json();

    fakeLlmText('Ringkasan BBM berhasil.');

    $requester = $this->admin;
    Permohonan::factory()->count(2)->create([
        'requester_id' => $requester->id,
        'status' => 'completed',
        'bill_amounts' => 200000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);

    $second = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), [
            'message' => 'grafik bbm',
            'session_id' => $first['session_id'],
        ])
        ->assertOk()
        ->json();

    expect($second['chart'])->not->toBeNull()
        ->and($second['chart']['labels'])->toContain('completed');

    $bbmMessage = AiChatMessage::where('role', 'assistant')->latest('id')->first();
    expect($bbmMessage->metadata['module'])->toBe('permohonan_bbm')
        ->and($bbmMessage->metadata['measure'])->toBe('bill_amounts');
});

it('menyimpan chart sebagai embedding di dalam konten pesan SQL', function () {
    fakeLlmText('Berikut grafiknya.');

    $fajar = User::factory()->create(['nama_lengkap' => 'Fajar']);
    $gita  = User::factory()->create(['nama_lengkap' => 'Gita']);
    Permohonan::factory()->create([
        'requester_id' => $fajar->id,
        'status' => 'completed',
        'bill_amounts' => 300000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);
    Permohonan::factory()->create([
        'requester_id' => $gita->id,
        'status' => 'completed',
        'bill_amounts' => 200000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Tampilkan grafik by nama'])
        ->assertOk();

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    $chart = \App\Support\AiChartEmbed::extract((string) $assistant->content);

    expect($assistant->hasChartEmbedding())->toBeTrue()
        ->and($chart)->not->toBeNull()
        ->and($chart['labels'])->toContain('Fajar', 'Gita')
        ->and($assistant->chartSpec()['labels'])->toContain('Fajar')
        ->and($assistant->displayContent())->not->toContain('AI_CHART')
        ->and($assistant->metadata['chart']['labels'])->toContain('Fajar');
});

it('marker embedding grafik tidak dikirim ke LLM pada percakapan lanjutan', function () {
    fakeLlmText('Grafik pertama.');

    $fajar = User::factory()->create(['nama_lengkap' => 'Fajar']);
    $gita  = User::factory()->create(['nama_lengkap' => 'Gita']);
    Permohonan::factory()->create([
        'requester_id' => $fajar->id,
        'status' => 'completed',
        'bill_amounts' => 300000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);
    Permohonan::factory()->create([
        'requester_id' => $gita->id,
        'status' => 'completed',
        'bill_amounts' => 200000,
        'request_date' => now()->format('Y-m-d H:i:s'),
    ]);

    $first = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Tampilkan grafik by nama'])
        ->assertOk()
        ->json();

    fakeLlmText('Jawaban lanjutan');

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), [
            'message' => 'kalau by nama saja?',
            'session_id' => $first['session_id'],
        ])
        ->assertOk();

    $bodies = collect(Http::recorded())
        ->flatten(1)
        ->filter(fn ($entry) => $entry instanceof \Illuminate\Http\Client\Request)
        ->map(fn ($r) => $r->body());
    expect($bodies)->not->toBeEmpty()
        ->and($bodies)->each->not->toContain('AI_CHART');
});

// MARKER_NEXT_TESTS

it('membuat line chart multi-series saat user minta "line chart per requester"', function () {
    fakeLlmText("Ini grafik trennya.\n\n[[CHART_TYPE: line]]");

    $fajar = User::factory()->create(['nama_lengkap' => 'Fajar']);
    $gita  = User::factory()->create(['nama_lengkap' => 'Gita']);
    Permohonan::factory()->create([
        'requester_id' => $fajar->id,
        'status' => 'completed',
        'bill_amounts' => 300000,
        'request_date' => '2026-01-15 10:00:00',
    ]);
    Permohonan::factory()->create([
        'requester_id' => $gita->id,
        'status' => 'completed',
        'bill_amounts' => 200000,
        'request_date' => '2026-01-20 10:00:00',
    ]);
    Permohonan::factory()->create([
        'requester_id' => $fajar->id,
        'status' => 'completed',
        'bill_amounts' => 150000,
        'request_date' => '2026-02-10 10:00:00',
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Tampilkan grafik BBM dalam bentuk line chart per requester'])
        ->assertOk();

    $chart = $response->json('chart');
    expect($chart)->not->toBeNull()
        ->and($chart['type'])->toBe('line')
        ->and($chart['labels'])->toBe(['2026-01', '2026-02'])
        ->and($chart['datasets'])->toHaveCount(2);

    $labels = collect($chart['datasets'])->pluck('label')->all();
    expect($labels)->toContain('Fajar', 'Gita');

    $fajarSeries = collect($chart['datasets'])->firstWhere('label', 'Fajar');
    expect($fajarSeries['values'])->toBe([300000, 150000]);

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['group_by'])->toBe('month')
        ->and($assistant->metadata['series_by'])->toBe('driver');
});

it('menghasilkan bar multi-series saat user minta "per bulan per status"', function () {
    fakeLlmText('Berikut perbandingan per status per bulan.');

    $req = User::factory()->create(['nama_lengkap' => 'Rudi']);
    Permohonan::factory()->create([
        'requester_id' => $req->id,
        'status' => 'completed',
        'bill_amounts' => 100000,
        'request_date' => '2026-01-10 10:00:00',
    ]);
    Permohonan::factory()->create([
        'requester_id' => $req->id,
        'status' => 'rejected',
        'bill_amounts' => 50000,
        'request_date' => '2026-01-11 10:00:00',
    ]);
    Permohonan::factory()->create([
        'requester_id' => $req->id,
        'status' => 'completed',
        'bill_amounts' => 200000,
        'request_date' => '2026-02-10 10:00:00',
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Tampilkan grafik BBM per bulan per status'])
        ->assertOk();

    $chart = $response->json('chart');
    expect($chart)->not->toBeNull()
        ->and($chart['type'])->toBe('bar')
        ->and($chart['labels'])->toBe(['2026-01', '2026-02'])
        ->and(collect($chart['datasets'])->pluck('label')->all())->toContain('completed', 'rejected');
});

it('menghargai sumbu X eksplisit "sumbu X bulan"', function () {
    fakeLlmText('Ini grafik per bulan.');

    $req = User::factory()->create(['nama_lengkap' => 'Sari']);
    Permohonan::factory()->create([
        'requester_id' => $req->id,
        'status' => 'completed',
        'bill_amounts' => 100000,
        'request_date' => '2026-01-10 10:00:00',
    ]);
    Permohonan::factory()->create([
        'requester_id' => $req->id,
        'status' => 'completed',
        'bill_amounts' => 200000,
        'request_date' => '2026-02-10 10:00:00',
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Tampilkan grafik dengan sumbu X bulan'])
        ->assertOk();

    $chart = $response->json('chart');
    expect($chart)->not->toBeNull()
        ->and($chart['x_label'])->toBe('Bulan')
        ->and($chart['labels'])->toBe(['2026-01', '2026-02']);
});

it('mengurutkan label waktu naik (kronologis) untuk line chart', function () {
    fakeLlmText("Tren bulanan.\n\n[[CHART_TYPE: line]]");

    $req = User::factory()->create(['nama_lengkap' => 'Tina']);
    Permohonan::factory()->create([
        'requester_id' => $req->id,
        'status' => 'completed',
        'bill_amounts' => 50000,
        'request_date' => '2026-03-10 10:00:00',
    ]);
    Permohonan::factory()->create([
        'requester_id' => $req->id,
        'status' => 'completed',
        'bill_amounts' => 90000,
        'request_date' => '2026-01-10 10:00:00',
    ]);
    Permohonan::factory()->create([
        'requester_id' => $req->id,
        'status' => 'completed',
        'bill_amounts' => 70000,
        'request_date' => '2026-02-10 10:00:00',
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Berapa tren pengeluaran BBM per bulan?'])
        ->assertOk();

    $chart = $response->json('chart');
    expect($chart)->not->toBeNull()
        ->and($chart['type'])->toBe('line')
        ->and($chart['labels'])->toBe(['2026-01', '2026-02', '2026-03'])
        ->and($chart['values'])->toBe([90000, 70000, 50000]);
});
