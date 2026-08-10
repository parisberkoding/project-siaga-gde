<?php

use App\Models\LpjPenggantian;
use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->admin = User::factory()->create(['role' => 'superadmin']);
    $role = \App\Models\Role::where('code', 'superadmin')->first();
    if ($role) {
        $this->admin->roles()->attach($role->id, ['is_primary' => true]);
    }
});

it('merender LPJ create dengan item upload-sederhana + Simpan Laporan', function () {
    $user = User::factory()->create(['role' => 'requester']);
    $role = \App\Models\Role::where('code', 'requester')->first();
    if ($role) {
        $user->roles()->attach($role->id, ['is_primary' => true]);
    }

    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    SuratSpd::factory()->create([
        'user_id'    => $user->id,
        'vehicle_id' => $vehicle->id,
        'status'     => 'approved',
    ]);

    $this->actingAs($user)
        ->get(route('requester.lpj.create'))
        ->assertStatus(200)
        ->assertSee('Simpan Laporan', false)
        ->assertSee('Foto Struk', false)
        ->assertDontSee('Analisis AI');
});

it('menyembunyikan budgeting per hari di detail surat (approve tanpa budget)', function () {
    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $surat = SuratSpd::factory()->create([
        'user_id'    => $this->admin->id,
        'vehicle_id' => $vehicle->id,
        'status'     => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->get(route('surat.superadmin.show', $surat))
        ->assertStatus(200)
        ->assertDontSee('Informasi Budget Per Hari')
        ->assertSee('Menyetujui surat tugas akan mengunci', false);

    // Approve tanpa payload budgets → harus sukses
    $this->post(route('surat.superadmin.approve', $surat))
        ->assertSessionHas('success');

    expect($surat->fresh()->status)->toBe('approved');
    expect($surat->fresh()->approved_by)->toBe($this->admin->id);
});
