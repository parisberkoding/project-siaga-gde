<?php

use App\Models\Role;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleDetail;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\MasterGasolineSeeder::class);

    // Site sebagian sudah di-seed oleh migration — lengkapi + petakan ke unit.
    $this->sitePTH   = Site::updateOrCreate(['code' => 'PTH'],   ['name' => 'Patuha', 'province_slug' => 'jawa-barat', 'is_active' => true]);
    $this->siteDieng = Site::updateOrCreate(['code' => 'Dieng'], ['name' => 'Dieng',  'province_slug' => 'jawa-tengah', 'is_active' => true]);

    $this->seed(\Database\Seeders\UnitSeeder::class);

    $this->unitPatuha = Unit::where('code', 'PATUHA')->firstOrFail();
    $this->unitDieng  = Unit::where('code', 'DIENG')->firstOrFail();

    $this->sitePTH->update(['unit_id' => $this->unitPatuha->id]);
    $this->siteDieng->update(['unit_id' => $this->unitDieng->id]);

    $this->admin = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    $this->admin->roles()->attach($role->id, ['is_primary' => true]);
});

/**
 * Payload form Kendaraan lengkap dengan identitas & dokumen.
 */
function vehiclePayload(array $override = []): array
{
    return array_merge([
        'consumerial_type'      => 'KENDARAAN',
        'consumerial_code'      => 'B 9001 ZZZ',
        'consumerial_name'      => 'Toyota Avanza',
        'consumerial_notes'     => null,
        'jenis_kendaraan'       => 'MPV',
        'merk'                  => 'Toyota',
        'model'                 => 'Avanza G',
        'tahun_pembuatan'       => 2021,
        'nomor_rangka'          => 'MHKM1BA3JMK012345',
        'nomor_mesin'           => '1NR-F123456',
        'nomor_bpkb'            => 'BPKB-0001',
        'nomor_stnk'            => 'STNK-0001',
        'nomor_kir'             => 'KIR-0001',
        'pajak_berlaku_sampai'  => '2027-01-31',
        'kir_berlaku_sampai'    => '2026-12-31',
        'kapasitas_mesin'       => 1300,
        'odometer'              => 85000,
        'status_kepemilikan'    => VehicleDetail::STATUS_SEWA,
        'warna'                 => 'Putih',
        'kapasitas_penumpang'   => 7,
        'volume_tangki'         => 45,
        'jenis_bahan_bakar'     => 'Pertamax',
        'nominal_full_tank'     => 450000,
        'nominal_spd_default'   => 200000,
        'kapasitas_bbm_tahunan' => 1200,
    ], $override);
}

it('menyimpan unit dan seluruh identitas kendaraan saat create', function () {
    $this->actingAs($this->admin)
        ->post(route('vehicles.store'), vehiclePayload([
            'unit_id' => $this->unitPatuha->id,
            'site_id' => $this->sitePTH->id,
        ]))
        ->assertSessionHas('success');

    $vehicle = VehicleAndToolsConsumers::where('consumerial_code', 'B 9001 ZZZ')->firstOrFail();

    expect($vehicle->unit_id)->toBe($this->unitPatuha->id);

    $detail = $vehicle->detail;
    expect($detail)->not->toBeNull();
    expect($detail->jenis_kendaraan)->toBe('MPV');
    expect($detail->merk)->toBe('Toyota');
    expect($detail->model)->toBe('Avanza G');
    expect((int) $detail->tahun_pembuatan)->toBe(2021);
    expect($detail->nomor_rangka)->toBe('MHKM1BA3JMK012345');
    expect($detail->nomor_mesin)->toBe('1NR-F123456');
    expect($detail->nomor_bpkb)->toBe('BPKB-0001');
    expect($detail->nomor_stnk)->toBe('STNK-0001');
    expect($detail->nomor_kir)->toBe('KIR-0001');
    expect($detail->pajak_berlaku_sampai->format('Y-m-d'))->toBe('2027-01-31');
    expect($detail->kir_berlaku_sampai->format('Y-m-d'))->toBe('2026-12-31');
    expect((int) $detail->kapasitas_mesin)->toBe(1300);
    expect((int) $detail->odometer)->toBe(85000);
    expect($detail->status_kepemilikan)->toBe(VehicleDetail::STATUS_SEWA);
    expect($detail->status_kepemilikan_label)->toBe('Sewa / Rental');
    expect($detail->site_id)->toBe($this->sitePTH->id);
});

it('menurunkan unit dari site ketika unit_id tidak dikirim', function () {
    $this->actingAs($this->admin)
        ->post(route('vehicles.store'), vehiclePayload([
            'unit_id' => null,
            'site_id' => $this->siteDieng->id,
        ]))
        ->assertSessionHas('success');

    $vehicle = VehicleAndToolsConsumers::where('consumerial_code', 'B 9001 ZZZ')->firstOrFail();

    expect($vehicle->unit_id)->toBe($this->unitDieng->id);
});

it('menyediakan dropdown unit dan field detailing di form create', function () {
    $this->actingAs($this->admin)
        ->get(route('vehicles.create'))
        ->assertStatus(200)
        ->assertSee('Unit Bisnis', false)
        ->assertSee('Nomor Rangka', false)
        ->assertSee('Nomor Mesin', false)
        ->assertSee('Nomor BPKB', false)
        ->assertSee('Status Kepemilikan', false)
        ->assertSee('Sewa / Rental', false)
        ->assertSee($this->unitPatuha->name, false);
});

it('menampilkan unit dan identitas kendaraan di form edit', function () {
    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 7777 EDT',
        'is_approved'      => true,
        'unit_id'          => $this->unitDieng->id,
    ]);
    VehicleDetail::factory()->create([
        'vehicle_and_tools_consumer_id' => $vehicle->id,
        'site_id'                       => $this->siteDieng->id,
        'nomor_rangka'                  => 'VIN-EDIT-777',
        'status_kepemilikan'            => VehicleDetail::STATUS_SEWA,
    ]);

    $this->actingAs($this->admin)
        ->get(route('vehicles.edit', $vehicle->id))
        ->assertStatus(200)
        ->assertSee('VIN-EDIT-777', false)
        ->assertSee('Sewa / Rental', false)
        ->assertSee('Unit Bisnis', false);
});

it('menolak create ketika site bukan milik unit yang dipilih', function () {
    $this->actingAs($this->admin)
        ->post(route('vehicles.store'), vehiclePayload([
            'unit_id' => $this->unitDieng->id,
            'site_id' => $this->sitePTH->id,
        ]))
        ->assertSessionHas('error');

    expect(VehicleAndToolsConsumers::where('consumerial_code', 'B 9001 ZZZ')->exists())->toBeFalse();
});

it('menyinkronkan unit_id kendaraan saat dipindah antar unit lewat update', function () {
    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'consumerial_code' => 'B 9002 ZZZ',
        'is_approved'      => true,
        'unit_id'          => $this->unitPatuha->id,
    ]);
    VehicleDetail::factory()->create([
        'vehicle_and_tools_consumer_id' => $vehicle->id,
        'site_id'                       => $this->sitePTH->id,
        'nomor_rangka'                  => null,
    ]);

    $this->actingAs($this->admin)
        ->post(route('vehicles.update', $vehicle->id), vehiclePayload([
            'consumerial_code' => 'B 9002 ZZZ',
            'unit_id'          => $this->unitDieng->id,
            'site_id'          => $this->siteDieng->id,
        ]))
        ->assertSessionHas('success');

    $vehicle->refresh();

    expect($vehicle->unit_id)->toBe($this->unitDieng->id);
    expect($vehicle->detail->nomor_rangka)->toBe('MHKM1BA3JMK012345');
    expect($vehicle->detail->site_id)->toBe($this->siteDieng->id);
    expect($vehicle->detail->status_kepemilikan)->toBe(VehicleDetail::STATUS_SEWA);
});

it('menampilkan kolom unit dan bisa difilter per unit di daftar kendaraan', function () {
    $patuha = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 1111 PAT',
        'is_approved'      => true,
        'unit_id'          => $this->unitPatuha->id,
    ]);
    $dieng = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 2222 DNG',
        'is_approved'      => true,
        'unit_id'          => $this->unitDieng->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('vehicles', ['tab' => 'list', 'unit_id' => $this->unitPatuha->id]))
        ->assertStatus(200)
        ->assertSee('B 1111 PAT', false)
        ->assertDontSee('B 2222 DNG', false)
        ->assertSee($this->unitPatuha->name, false);
});

it('bisa memfilter kendaraan yang belum punya unit', function () {
    VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 8888 NON',
        'is_approved'      => true,
        'unit_id'          => null,
    ]);
    VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 9999 PAT',
        'is_approved'      => true,
        'unit_id'          => $this->unitPatuha->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('vehicles', ['tab' => 'list', 'unit_id' => 'none']))
        ->assertStatus(200)
        ->assertSee('B 8888 NON', false)
        ->assertDontSee('B 9999 PAT', false)
        ->assertSee('Belum ada unit', false);
});

it('menampilkan identitas kendaraan di halaman detail', function () {
    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 3333 DTL',
        'is_approved'      => true,
        'unit_id'          => $this->unitPatuha->id,
    ]);
    VehicleDetail::factory()->create([
        'vehicle_and_tools_consumer_id' => $vehicle->id,
        'site_id'                       => $this->sitePTH->id,
        'nomor_rangka'                  => 'VIN-DETAIL-001',
        'nomor_mesin'                   => 'MESIN-DETAIL-001',
        'nomor_bpkb'                    => 'BPKB-DETAIL-001',
        'status_kepemilikan'            => VehicleDetail::STATUS_SEWA,
    ]);

    $this->actingAs($this->admin)
        ->get(route('superadmin.vehicle.detail', $vehicle->id))
        ->assertStatus(200)
        ->assertSee('VIN-DETAIL-001', false)
        ->assertSee('MESIN-DETAIL-001', false)
        ->assertSee('BPKB-DETAIL-001', false)
        ->assertSee('Sewa / Rental', false)
        ->assertSee($this->unitPatuha->name, false);
});

it('membatasi kendaraan yang tampil di form permohonan BBM sesuai unit driver', function () {
    $patuha = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 4444 PAT',
        'is_approved'      => true,
        'is_active'        => true,
        'unit_id'          => $this->unitPatuha->id,
    ]);
    $dieng = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 5555 DNG',
        'is_approved'      => true,
        'is_active'        => true,
        'unit_id'          => $this->unitDieng->id,
    ]);

    $driver = User::factory()->create(['role' => 'requester', 'unit_id' => $this->unitPatuha->id]);
    $driverRole = Role::where('code', 'requester')->first();
    $driver->roles()->attach($driverRole->id, ['is_primary' => true]);

    $this->actingAs($driver)
        ->get(route('requester-index'))
        ->assertStatus(200)
        ->assertSee('B 4444 PAT', false)
        ->assertDontSee('B 5555 DNG', false);
});

it('menolak permohonan BBM yang memakai plat nomor unit lain', function () {
    $dieng = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 6666 DNG',
        'is_approved'      => true,
        'is_active'        => true,
        'unit_id'          => $this->unitDieng->id,
    ]);

    $driver = User::factory()->create(['role' => 'requester', 'unit_id' => $this->unitPatuha->id]);
    $driverRole = Role::where('code', 'requester')->first();
    $driver->roles()->attach($driverRole->id, ['is_primary' => true]);

    $this->actingAs($driver)
        ->post(route('requester-create'), [
            'vehicle_id'   => $dieng->id,
            'gasoline_type' => 'Pertamax',
            'bill_payment' => 450000,
        ])
        ->assertSessionHas('error');

    expect(\App\Models\Permohonan::count())->toBe(0);
});
