<?php

use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->admin = User::factory()->create(['role' => 'superadmin']);
    $this->admin->roles()->attach(Role::where('code', 'superadmin')->first()->id, ['is_primary' => true]);

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $this->surat = SuratSpd::factory()->create([
        'user_id'    => $this->admin->id,
        'vehicle_id' => $this->vehicle->id,
        'status'     => 'approved',
    ]);
});

it('SPD berstatus approved tetap bisa dicetak', function () {
    $this->actingAs($this->admin)
        ->get(route('spd.surat.print', $this->surat))
        ->assertStatus(200)
        ->assertSee($this->surat->nomor_surat, false);
});

it('SPD berstatus completed (selesai) tetap bisa dicetak untuk arsip', function () {
    $this->surat->update(['status' => 'completed']);

    $this->actingAs($this->admin)
        ->get(route('spd.surat.print', $this->surat))
        ->assertStatus(200)
        ->assertSee($this->surat->nomor_surat, false);
});
