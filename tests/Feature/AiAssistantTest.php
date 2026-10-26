<?php

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Role;
use App\Models\User;
use App\Services\GeminiAssistantService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->admin = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    $this->admin->roles()->attach($role->id, ['is_primary' => true]);
});

it('menyajikan halaman AI assistant untuk superadmin', function () {
    $this->actingAs($this->admin)
        ->get(route('ai-assistant.index'))
        ->assertStatus(200)
        ->assertSee('AI Assistant', false)
        ->assertSee('Groq', false);
});

it('membuat sesi dan pesan di database saat pertanyaan dikirim tanpa API key', function () {
    config(['services.groq.api_key' => null]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), [
            'message' => 'Berapa total pengeluaran bulan ini?',
        ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonMissingPath('error');

    expect(AiChatSession::where('user_id', $this->admin->id)->count())->toBe(1);
    expect(AiChatMessage::where('role', 'user')->count())->toBe(1);
    expect(AiChatMessage::where('role', 'assistant')->count())->toBe(1);
    expect(AiChatMessage::where('role', 'assistant')->first()->content)
        ->toContain('belum dikonfigurasi');
});

it('menjawab tanpa menyentuh API eksternal ketika tidak dikonfigurasi', function () {
    config(['services.groq.api_key' => null]);

    Http::fake();

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Halo']);

    Http::assertNothingSent();
});

it('menggunakan sesi yang sama untuk pertanyaan lanjutan', function () {
    config(['services.groq.api_key' => null]);

    $first = $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), ['message' => 'Pertanyaan pertama'])
        ->json();

    $this->actingAs($this->admin)
        ->postJson(route('ai-assistant.send'), [
            'message'    => 'Pertanyaan kedua',
            'session_id' => $first['session_id'],
        ])
        ->assertOk()
        ->assertJsonPath('session_id', $first['session_id']);

    expect(AiChatSession::count())->toBe(1);
    expect(AiChatMessage::count())->toBe(4);
});

it('superadmin dapat menghapus sesi chat miliknya', function () {
    $session = AiChatSession::create(['user_id' => $this->admin->id, 'title' => 'Tes']);
    AiChatMessage::create(['chat_session_id' => $session->id, 'role' => 'user', 'content' => 'Halo']);

    $this->actingAs($this->admin)
        ->deleteJson(route('ai-assistant.sessions.destroy', $session->id))
        ->assertOk();

    expect(AiChatSession::count())->toBe(0);
    expect(AiChatMessage::count())->toBe(0);
});

it('tidak mengizinkan akses untuk user selain superadmin', function () {
    $user = User::factory()->create(['role' => 'requester']);

    $this->actingAs($user)
        ->get(route('ai-assistant.index'))
        ->assertStatus(403);
});

it('menggunakan token (bukan ID numerik) untuk tautan sesi di URL', function () {
    $session = AiChatSession::create(['user_id' => $this->admin->id, 'title' => 'Tes SPD']);

    expect($session->session_key)->not->toBeNull()
        ->and(strlen($session->session_key))->toBe(32);

    // Tautan sidebar memakai session_key — ID database tidak muncul di URL.
    $this->actingAs($this->admin)
        ->get(route('ai-assistant.index'))
        ->assertOk()
        ->assertSee(route('ai-assistant.index', ['session' => $session->session_key]), false)
        ->assertDontSee('session='.$session->id);

    // Sesi tetap bisa dibuka lewat token.
    $this->actingAs($this->admin)
        ->get(route('ai-assistant.index', ['session' => $session->session_key]))
        ->assertOk()
        ->assertSee($session->title);
});

it('menampilkan kembali visualisasi (grafik) yang sudah tersimpan saat sesi dibuka ulang via token', function () {
    $session = AiChatSession::create(['user_id' => $this->admin->id, 'title' => 'Top 5 SPD']);

    $chart = [
        'type'     => 'bar',
        'title'    => 'Total per driver',
        'labels'   => ['Faris Test Driver', 'Monica Wilderman MD'],
        'values'   => [18349000, 0],
        'currency' => true,
        'x_label'  => 'Driver',
        'y_label'  => 'MAX total budget',
    ];

    AiChatMessage::create([
        'chat_session_id' => $session->id,
        'role'            => 'assistant',
        'content'         => "Top 5 Request SPD\n\n| Nama Driver | Jumlah Transaksi | Nilai Total |\n|---|---|---|\n| Faris Test Driver | 14 | Rp 18.349.000 |"
            .\App\Support\AiChartEmbed::embed($chart),
        'metadata' => ['chart' => $chart],
    ]);

    // Muat ulang sesi — tabel & grafik tetap dirender dari database (bukan ID).
    $this->actingAs($this->admin)
        ->get(route('ai-assistant.index', ['session' => $session->session_key]))
        ->assertOk()
        ->assertSee('Top 5 Request SPD', false)
        ->assertSee('ai-chart-box', false)
        ->assertSee('Faris Test Driver', false)
        ->assertSee('data-chart=', false)
        ->assertDontSee('AI_CHART');
});
