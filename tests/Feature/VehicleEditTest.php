<?php

use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleDetail;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\MasterGasolineSeeder::class);

    $this->admin = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    $this->admin->roles()->attach($role->id, ['is_primary' => true]);

    $site = Site::where('code', 'Patuha')->orWhere('code', 'PTH')->first();
    if ($site) {
        $site->update(['province_slug' => 'jawa-barat']);
    }

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);
    VehicleDetail::factory()->create([
        'vehicle_and_tools_consumer_id' => $this->vehicle->id,
        'site_id'                       => $site?->id,
        'jenis_bahan_bakar'             => 'Pertamax',
        'volume_tangki'                 => 57,
        'nominal_full_tank'             => 900000,
    ]);
});

it('menyajikan halaman edit kendaraan untuk superadmin', function () {
    $this->actingAs($this->admin)
        ->get(route('vehicles.edit', $this->vehicle->id))
        ->assertStatus(200)
        ->assertSee('Edit Kendaraan / Alat', false)
        ->assertSee('Benchmark', false);
});

it('mengupdate benchmark kendaraan lewat form edit', function () {
    $this->actingAs($this->admin)
        ->post(route('vehicles.update', $this->vehicle->id), [
            'consumerial_type'   => 'KENDARAAN',
            'consumerial_code'   => $this->vehicle->consumerial_code,
            'consumerial_name'   => $this->vehicle->consumerial_name,
            'volume_tangki'      => 55,
            'jenis_bahan_bakar'  => 'Pertamax',
            'nominal_full_tank'  => 1500000,
            'nominal_spd_default' => 200000,
            'kapasitas_penumpang' => 7,
        ])
        ->assertSessionHas('success');

    $detail = $this->vehicle->fresh()->detail;
    expect((float) $detail->nominal_full_tank)->toBe(1500000.0);
    expect((float) $detail->volume_tangki)->toBe(55.0);
    expect($detail->jenis_bahan_bakar)->toBe('Pertamax');
    expect((float) $detail->nominal_spd_default)->toBe(200000.0);
    expect((int) $detail->kapasitas_penumpang)->toBe(7);
});

it('menampilkan daftar kendaraan dengan kolom benchmark', function () {
    $this->actingAs($this->admin)
        ->get(route('vehicles', ['tab' => 'list']))
        ->assertStatus(200)
        ->assertSee('Benchmark BBM', false)
        ->assertSee('Rp 900.000', false);
});
