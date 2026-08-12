<?php

use App\Models\Permohonan;
use App\Models\Role;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    $this->superadmin->roles()->attach($role->id, ['is_primary' => true]);

    $this->manager = User::factory()->create(['role' => 'manager']);
    $role = Role::where('code', 'manager')->first();
    $this->manager->roles()->attach($role->id, ['is_primary' => true]);
});

it('superadmin dapat membuka dashboard bisnis tanpa error', function () {
    $this->actingAs($this->superadmin)
        ->get(route('superadmin.dashboard.bisnis'))
        ->assertOk()
        ->assertSee('Dashboard Bisnis')
        ->assertSee('Filter Data Dashboard');
});

it('manager dapat membuka dashboard bisnis', function () {
    $this->actingAs($this->manager)
        ->get(route('superadmin.dashboard.bisnis'))
        ->assertOk();
});

it('dashboard bisnis menolak user tanpa role manager/superadmin', function () {
    $requester = User::factory()->create(['role' => 'requester']);
    $role = Role::where('code', 'requester')->first();
    $requester->roles()->attach($role->id, ['is_primary' => true]);

    $this->actingAs($requester)
        ->get(route('superadmin.dashboard.bisnis'))
        ->assertForbidden();
});

it('filter periode day/month/year tetap bekerja pada dashboard bisnis', function () {
    Permohonan::factory()->create([
        'requester_id' => $this->superadmin->id,
        'status' => 'approved',
        'bill_amounts' => 250000,
        'request_date' => now(),
    ]);

    foreach (['day', 'month', 'year'] as $period) {
        $this->actingAs($this->superadmin)
            ->get(route('superadmin.dashboard.bisnis', ['period' => $period]))
            ->assertOk();
    }
});

it('filter transaksi (status + kendaraan + nominal) tidak error', function () {
    $requester = User::factory()->create(['role' => 'requester']);
    Permohonan::factory()->create([
        'requester_id' => $requester->id,
        'status' => 'completed',
        'bill_amounts' => 200000,
        'request_date' => now(),
    ]);

    $this->actingAs($this->superadmin)
        ->get(route('superadmin.dashboard.bisnis', [
            'status' => 'completed',
            'nominal_range' => '100000-300000',
        ]))
        ->assertOk();
});
