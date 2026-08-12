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
