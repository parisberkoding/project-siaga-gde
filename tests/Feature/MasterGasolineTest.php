<?php

use App\Models\MasterGasoline;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleDetail;
use App\Services\FuelBenchmarkService;
use App\Services\GasolinePriceSyncService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\MasterGasolineSeeder::class);

    $this->admin = User::factory()->create(['role' => 'superadmin']);
    $adminRole = Role::where('code', 'superadmin')->first();
    $this->admin->roles()->attach($adminRole->id, ['is_primary' => true]);

    $this->requester = User::factory()->create(['role' => 'requester']);
    $requesterRole = Role::where('code', 'requester')->first();
    $this->requester->roles()->attach($requesterRole->id, ['is_primary' => true]);

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
        'kapasitas_penumpang'           => 5,
    ]);
});

it('menyimpan harga BBM dari MasterGasolineSeeder', function () {
    expect(MasterGasoline::where('province_slug', 'jawa-barat')->where('fuel_type', 'PERTAMAX')->count())->toBe(1);
    expect(MasterGasoline::count())->toBe(6);
});

it('menghitung benchmark kendaraan dari tangki x harga region', function () {
    // 57 L x Rp 15.950 = 909.150 -> dibulatkan ke kelipatan 50.000 = 900.000
    expect($this->vehicle->fresh()->fuel_benchmark)->toBe(900000);
    expect($this->vehicle->fresh()->fuel_benchmark_range)->toBe(['min' => 400000, 'max' => 1400000]);
});

it('menyajikan halaman Master Harga BBM untuk superadmin', function () {
    $this->actingAs($this->admin)
        ->get(route('master-gasoline.index'))
        ->assertStatus(200)
        ->assertSee('PERTAMAX', false);
});

it('menyajikan halaman requester dengan opsi nominal berbasis benchmark', function () {
    $this->actingAs($this->requester)
        ->get(route('requester-index'))
        ->assertStatus(200)
        ->assertSee('900000', false);
});

it('menerima permohonan dengan nominal dalam rentang benchmark', function () {
    $this->actingAs($this->requester)
        ->post(route('requester-create'), [
            'vehicle_id'      => $this->vehicle->id,
            'gasoline_type'   => 'Pertamax',
            'bill_payment'    => 900000,
            'requester_notes' => 'test',
        ])
        ->assertSessionHas('success');

    expect(\App\Models\Permohonan::where('consumerial_tools_id', $this->vehicle->id)->count())->toBe(1);
});

it('menolak nominal di luar rentang benchmark', function () {
    $this->actingAs($this->requester)
        ->post(route('requester-create'), [
            'vehicle_id'      => $this->vehicle->id,
            'gasoline_type'   => 'Pertamax',
            'bill_payment'    => 300000, // di bawah min 400.000
            'requester_notes' => 'test',
        ])
        ->assertSessionHasErrors('bill_payment');

    expect(\App\Models\Permohonan::where('consumerial_tools_id', $this->vehicle->id)->count())->toBe(0);
});

it('menolak nominal bukan kelipatan 50.000', function () {
    $this->actingAs($this->requester)
        ->post(route('requester-create'), [
            'vehicle_id'      => $this->vehicle->id,
            'gasoline_type'   => 'Pertamax',
            'bill_payment'    => 912345,
            'requester_notes' => 'test',
        ])
        ->assertSessionHasErrors('bill_payment');
});
