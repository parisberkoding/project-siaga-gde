<?php

use App\Models\LpjPenggantian;
use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->driver = User::factory()->create(['role' => 'requester']);
    $role = Role::where('code', 'requester')->first();
    if ($role) {
        $this->driver->roles()->attach($role->id, ['is_primary' => true]);
    }

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);
});

it('SPD Berjalan menampilkan surat approved yang belum dilaporkan', function () {
    $surat = SuratSpd::factory()->create([
        'user_id'    => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'status'     => 'approved',
        'nomor_surat' => '999.TGS/PTH1.30-GDE/X/2026',
    ]);

    $this->actingAs($this->driver)
        ->get(route('requester.spd.berjalan'))
        ->assertStatus(200)
        ->assertSee('SPD Berjalan', false)
        ->assertSee('Surat Tugas yang Harus Dilaporkan', false)
        ->assertSee($surat->nomor_surat, false)
        ->assertSee('Lapor &amp; Validasi SPD', false);
});

it('SPD Berjalan tidak menampilkan surat yang sudah punya LPJ', function () {
    $sudahDilaporkan = SuratSpd::factory()->create([
        'user_id'    => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'status'     => 'approved',
        'nomor_surat' => '888.TGS/PTH1.30-GDE/X/2026',
    ]);

    LpjPenggantian::create([
        'surat_spd_id' => $sudahDilaporkan->id,
        'user_id'      => $this->driver->id,
        'vehicle_id'   => $this->vehicle->id,
        'nomor_polisi' => 'D 1234 TST',
        'nama'         => 'Driver Test',
        'jabatan'      => 'Driver',
        'uraian'       => 'Perjalanan dinas uji',
        'status'       => 'pending',
    ]);

    $this->actingAs($this->driver)
        ->get(route('requester.spd.berjalan'))
        ->assertStatus(200)
        ->assertDontSee('888.TGS/PTH1.30-GDE/X/2026', false)
        ->assertSee('Tidak ada surat SPD yang perlu dilaporkan', false);
});

it('sidebar menampilkan submenu SPD Berjalan dan menyembunyikan menu Pelaporan', function () {
    $this->actingAs($this->driver)
        ->get(route('requester.spd.berjalan'))
        ->assertStatus(200)
        ->assertSee('SPD Berjalan', false)
        ->assertDontSee('Pelaporan BBM &amp; SPD', false);
});
