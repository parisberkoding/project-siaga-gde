<?php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('path traversal ditolak', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'superadmin']);

    $res = $this->actingAs($admin)->get('/files/storage/....//....//....//.env');
    expect($res->status())->toBe(404);
});

test('file storage butuh autentikasi', function () {
    $res = $this->get('/files/storage/struk/test.jpg');
    expect($res->status())->toBe(302)->and($res->headers->get('Location'))->toContain('login');
});
