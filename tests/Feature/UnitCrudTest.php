<?php

use App\Models\Role;
use App\Models\Unit;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    $this->superadmin->roles()->attach($role->id, ['is_primary' => true]);
});

it('menampilkan daftar unit bisnis', function () {
    $this->actingAs($this->superadmin)
        ->get(route('superadmin.units.index'))
        ->assertOk()
        ->assertSee('Unit Bisnis');
});

it('membuat unit baru', function () {
    $this->actingAs($this->superadmin)
        ->post(route('superadmin.units.store'), [
            'code' => 'TEST',
            'name' => 'Unit Test',
            'description' => 'Unit untuk pengujian',
            'is_active' => 1,
        ])
        ->assertRedirect(route('superadmin.units.index'))
        ->assertSessionHas('success');

    $unit = Unit::where('code', 'TEST')->first();
    expect($unit)->not->toBeNull();
    expect($unit->name)->toBe('Unit Test');
});

it('menolak kode unit duplikat', function () {
    $this->actingAs($this->superadmin)
        ->post(route('superadmin.units.store'), [
            'code' => 'PATUHA',
            'name' => 'Duplikat',
        ])
        ->assertSessionHasErrors('code');
});

it('mengupdate nama unit', function () {
    $unit = Unit::where('code', 'PATUHA')->first();

    $this->actingAs($this->superadmin)
        ->put(route('superadmin.units.update', $unit), [
            'name' => 'Unit Patuha Baru',
            'description' => 'Updated',
            'is_active' => 1,
        ])
        ->assertRedirect(route('superadmin.units.index'));

    expect($unit->fresh()->name)->toBe('Unit Patuha Baru');
});

it('menolak hapus unit yang masih dipakai user', function () {
    $unit = Unit::where('code', 'PUSAT')->first();
    User::factory()->create(['role' => 'general_affairs', 'unit_id' => $unit->id]);

    $this->actingAs($this->superadmin)
        ->post(route('superadmin.units.delete', $unit))
        ->assertSessionHas('error');

    expect(Unit::find($unit->id))->not->toBeNull();
});

it('menolak hapus unit yang masih memiliki site', function () {
    $unit = Unit::where('code', 'DIENG')->first();
    $before = $unit->sites()->count();
    $unit->sites()->create(['code' => 'DIENG-X', 'name' => 'Dieng X', 'is_active' => true]);

    $this->actingAs($this->superadmin)
        ->post(route('superadmin.units.delete', $unit))
        ->assertSessionHas('error');

    expect(Unit::find($unit->id))->not->toBeNull();
    expect($unit->sites()->count())->toBe($before + 1);
});

it('menghapus unit yang tidak dipakai user dan tidak punya site', function () {
    $unit = Unit::create(['code' => 'NEW', 'name' => 'Unit Baru', 'is_active' => true]);

    $this->actingAs($this->superadmin)
        ->post(route('superadmin.units.delete', $unit))
        ->assertSessionHas('success');

    expect(Unit::find($unit->id))->toBeNull();
});

it('menyimpan unit_id saat user manual dibuat', function () {
    $unit = Unit::where('code', 'PATUHA')->first();

    $this->actingAs($this->superadmin)
        ->post(route('users.store'), [
            'mode' => 'manual',
            'username' => 'ga.patuha',
            'password' => 'rahasia123',
            'nama_lengkap' => 'GA Patuha',
            'email' => 'ga.patuha@geodipa.test',
            'role_code' => 'general_affairs',
            'unit_id' => $unit->id,
        ])
        ->assertRedirect();

    $user = User::where('username', 'ga.patuha')->first();
    expect($user->unit_id)->toBe($unit->id);
});

it('mengupdate unit_id saat user diedit', function () {
    $user = User::factory()->create(['role' => 'general_affairs', 'username' => 'ga.edit', 'email' => 'ga.edit@geodipa.test']);
    $role = Role::where('code', 'general_affairs')->first();
    $user->roles()->attach($role->id, ['is_primary' => true]);

    $unit = Unit::where('code', 'DIENG')->first();

    $this->actingAs($this->superadmin)
        ->post(route('users.update', $user->id), [
            'username' => $user->username,
            'nama_lengkap' => $user->nama_lengkap,
            'email' => $user->email,
            'role_code' => 'general_affairs',
            'unit_id' => $unit->id,
        ])
        ->assertRedirect();

    expect($user->fresh()->unit_id)->toBe($unit->id);
});
