<?php

use App\Models\Role;
use App\Models\Unit;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);

    $this->patuha = Unit::where('code', 'PATUHA')->first();
    $this->dieng = Unit::where('code', 'DIENG')->first();
});

/** Superadmin uji (untuk memanggil route Manajemen User). */
function unitSuperadmin(): User
{
    $u = User::factory()->create(['role' => 'superadmin']);
    $u->roles()->attach(Role::where('code', 'superadmin')->first()->id, ['is_primary' => true]);

    return User::find($u->id);
}

/** Role khusus unit. */
function unitRole(string $code, Unit $unit): Role
{
    return Role::create([
        'code' => $code,
        'name' => $code,
        'unit_id' => $unit->id,
        'is_active' => true,
        'is_system' => false,
    ]);
}

/* =========================================================
 * Aturan isAssignableTo
 * =======================================================*/

it('role global berlaku untuk user mana pun', function () {
    $global = Role::where('code', 'requester')->first();

    expect($global->isGlobal())->toBeTrue();
    expect($global->isAssignableTo(null))->toBeTrue();
    expect($global->isAssignableTo($this->patuha->id))->toBeTrue();
});

it('role ber-unit hanya berlaku untuk user di unit itu', function () {
    $role = unitRole('patuha_officer', $this->patuha);

    expect($role->isAssignableTo($this->patuha->id))->toBeTrue();
    expect($role->isAssignableTo($this->dieng->id))->toBeFalse();
    expect($role->isAssignableTo(null))->toBeFalse();
});

it('scopeAvailableFor mengembalikan role global + milik unit', function () {
    unitRole('patuha_officer', $this->patuha);
    unitRole('dieng_officer', $this->dieng);

    $forPatuha = Role::availableFor($this->patuha->id)->pluck('code')->all();

    expect($forPatuha)->toContain('patuha_officer');
    expect($forPatuha)->toContain('requester');          // global
    expect($forPatuha)->not->toContain('dieng_officer');

    // user tanpa unit hanya mendapat role global
    $forNull = Role::availableFor(null)->pluck('code')->all();
    expect($forNull)->not->toContain('patuha_officer');
    expect($forNull)->toContain('requester');
});

/* =========================================================
 * assignRole sebagai jalur tunggal
 * =======================================================*/

it('assignRole menolak role milik unit lain', function () {
    $role = unitRole('patuha_officer', $this->patuha);
    $user = User::factory()->create(['role' => 'requester', 'unit_id' => $this->dieng->id]);

    expect(fn () => $user->assignRole($role))->toThrow(\InvalidArgumentException::class);
    expect($user->roles()->count())->toBe(0);
});

it('assignRole menerima role yang cocok dengan unit user', function () {
    $role = unitRole('patuha_officer', $this->patuha);
    $user = User::factory()->create(['role' => 'requester', 'unit_id' => $this->patuha->id]);

    $user->assignRole($role);

    expect($user->fresh()->roles()->pluck('code')->all())->toBe(['patuha_officer']);
    expect($user->fresh()->role)->toBe('patuha_officer');
});

/* =========================================================
 * Enforce di Manajemen User (store/update)
 * =======================================================*/

it('menolak membuat user di unit yang tidak memiliki role itu', function () {
    $role = unitRole('patuha_officer', $this->patuha);

    $this->actingAs(unitSuperadmin())
        ->post(route('users.store'), [
            'mode' => 'manual',
            'username' => 'orang.dieng',
            'password' => 'rahasia123',
            'nama_lengkap' => 'Orang Dieng',
            'email' => 'orang.dieng@geodipa.test',
            'role_code' => $role->code,
            'unit_id' => $this->dieng->id,   // unit salah untuk role itu
        ])
        ->assertSessionHas('error');

    expect(User::where('username', 'orang.dieng')->exists())->toBeFalse();
});

it('menerima membuat user bila role cocok dengan unitnya', function () {
    $role = unitRole('patuha_officer', $this->patuha);

    $this->actingAs(unitSuperadmin())
        ->post(route('users.store'), [
            'mode' => 'manual',
            'username' => 'orang.patuha',
            'password' => 'rahasia123',
            'nama_lengkap' => 'Orang Patuha',
            'email' => 'orang.patuha@geodipa.test',
            'role_code' => $role->code,
            'unit_id' => $this->patuha->id,
        ])
        ->assertRedirect();

    expect(User::where('username', 'orang.patuha')->first()->unit_id)->toBe($this->patuha->id);
});

/* =========================================================
 * UI RBAC
 * =======================================================*/

it('menampilkan kolom Cakupan dan dapat membuat role ber-unit', function () {
    $this->actingAs(unitSuperadmin())
        ->post(route('superadmin.rbac.store'), [
            'code' => 'dieng_viewer',
            'name' => 'Dieng Viewer',
            'unit_id' => $this->dieng->id,
        ])
        ->assertRedirect(route('superadmin.rbac.index'));

    $role = Role::where('code', 'dieng_viewer')->first();
    expect($role->unit_id)->toBe($this->dieng->id);
    expect($role->isGlobal())->toBeFalse();
});
