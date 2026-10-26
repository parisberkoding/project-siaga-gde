<?php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('request autentikasi tanpa sidik jari menyimpan device fingerprint', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)->get('/profile');

    $this->assertAuthenticated();
    expect(session('device_fingerprint'))->not->toBeNull();
});

test('perubahan device fingerprint memaksa login ulang', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)
        ->withSession(['device_fingerprint' => 'hash-yang-salah'])
        ->get('/profile')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
