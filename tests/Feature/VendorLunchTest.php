<?php

use App\Models\Role;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
});

it('menyediakan role vendor dengan permission lunch.view', function () {
    $vendor = Role::where('code', 'vendor')->first();
    expect($vendor)->not->toBeNull();
    expect($vendor->permissions->pluck('code'))->toContain('lunch.view');
});

it('superadmin bisa mengakses halaman siapa makan hari ini', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    $superadmin->roles()->attach($role->id, ['is_primary' => true]);

    $this->actingAs($superadmin)
        ->get(route('vendor.lunch.today'))
        ->assertStatus(200)
        ->assertSee('Daftar Siapa Makan Hari Ini', false);
});

it('vendor bisa mengakses halaman kehadiran lunch', function () {
    $vendor = User::factory()->create(['role' => 'vendor']);
    $role = Role::where('code', 'vendor')->first();
    $vendor->roles()->attach($role->id, ['is_primary' => true]);

    $this->actingAs($vendor)
        ->get(route('vendor.lunch.today'))
        ->assertStatus(200);
});

it('vendor tidak bisa membuka dashboard/forecast admin lunch', function () {
    $vendor = User::factory()->create(['role' => 'vendor']);
    $role = Role::where('code', 'vendor')->first();
    $vendor->roles()->attach($role->id, ['is_primary' => true]);

    $this->actingAs($vendor)
        ->get(route('lunch.dashboard'))
        ->assertForbidden();

    $this->actingAs($vendor)
        ->get(route('lunch.forecasts.index'))
        ->assertForbidden();
});

it('requester tidak bisa mengakses halaman vendor lunch', function () {
    $requester = User::factory()->create(['role' => 'requester']);
    $role = Role::where('code', 'requester')->first();
    $requester->roles()->attach($role->id, ['is_primary' => true]);

    $this->actingAs($requester)
        ->get(route('vendor.lunch.today'))
        ->assertForbidden();
});
