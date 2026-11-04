<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RbacSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->superadmin->roles()->attach(
        Role::where('code', 'superadmin')->firstOrFail()->id,
        ['is_primary' => true]
    );
});

it('menyimpan semua nama role dalam format lowercase snake_case', function () {
    foreach (Role::all() as $role) {
        expect($role->name)->toMatch('/^[a-z0-9]+(_[a-z0-9]+)*$/');
        expect($role->name)->toBe(strtolower($role->name));
    }

    expect(Role::where('code', 'requester')->value('name'))->toBe('requester');
    expect(Role::where('code', 'general_affairs')->value('name'))->toBe('general_affairs');
});

it('menampilkan label role lowercase di halaman tambah user', function () {
    $this->actingAs($this->superadmin)
        ->get(route('users.create'))
        ->assertOk()
        ->assertSee('requester')
        ->assertDontSee('Driver / Requester');
});

it('menormalkan nama role baru menjadi snake_case', function () {
    $this->actingAs($this->superadmin)
        ->post(route('superadmin.rbac.store'), [
            'code' => 'bbm_officer',
            'name' => 'BBM Officer',
        ])
        ->assertRedirect(route('superadmin.rbac.index'));

    expect(Role::where('code', 'bbm_officer')->value('name'))->toBe('bbm_officer');
});

it('tidak lagi menampilkan keterangan Hanya akun ber-role di form shortcut SPD', function () {
    $this->actingAs($this->superadmin)
        ->get(route('surat.superadmin.create-shortcut'))
        ->assertOk()
        ->assertDontSee('Hanya akun ber-role')
        ->assertSee('Pengemudi lain (tanpa akun) tetap bisa diisi manual.');
});
