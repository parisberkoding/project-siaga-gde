<?php

use App\Models\Permohonan;
use App\Models\Role;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->admin = User::factory()->create(['role' => 'admin']);
    $role = Role::where('code', 'general_affairs')->first();
    $this->admin->roles()->attach($role->id, ['is_primary' => true]);
});

it('menyajikan halaman admin dashboard dengan card permohonan pending', function () {
    $this->actingAs($this->admin)
        ->get(route('admin-dashboard'))
        ->assertStatus(200)
        ->assertSee('Permohonan BBM', false);
});

it('bulk process menyimpan komentar admin sebagai authorizer_notes', function () {
    $requester = User::factory()->create(['role' => 'requester']);
    $permohonan = Permohonan::factory()->create([
        'requester_id' => $requester->id,
        'status'       => 'pending',
        'bill_amounts' => 100000,
        'request_date' => now(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin-bulk-process'), [
            'decisions' => json_encode([
                ['request_id' => $permohonan->id, 'status' => 'approve', 'note' => 'Lanjutkan saja'],
            ]),
        ])
        ->assertRedirect(route('admin-dashboard'))
        ->assertSessionHas('success');

    $fresh = $permohonan->fresh();
    expect($fresh->status)->toBe('approved');
    expect($fresh->authorizer_notes)->toContain('Lanjutkan saja');
    expect($fresh->authorizer_notes)->toStartWith('[ADMIN APPROVAL]');
});

it('bulk process tanpa komentar tetap menghasilkan catatan default', function () {
    $requester = User::factory()->create(['role' => 'requester']);
    $permohonan = Permohonan::factory()->create([
        'requester_id' => $requester->id,
        'status'       => 'pending',
        'bill_amounts' => 100000,
        'request_date' => now(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin-bulk-process'), [
            'decisions' => json_encode([
                ['request_id' => $permohonan->id, 'status' => 'approve'],
            ]),
        ])
        ->assertRedirect(route('admin-dashboard'));

    expect($permohonan->fresh()->authorizer_notes)->toBe('[ADMIN APPROVAL]');
});
