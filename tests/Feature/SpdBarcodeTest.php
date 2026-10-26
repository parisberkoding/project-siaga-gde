<?php

use App\Models\LpjPenggantian;
use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->admin = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    if ($role) {
        $this->admin->roles()->attach($role->id, ['is_primary' => true]);
    }

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $this->surat = SuratSpd::factory()->create([
        'user_id'             => $this->admin->id,
        'vehicle_id'          => $this->vehicle->id,
        'status'              => 'approved',
        'pemberi_tugas_nama'  => 'Genny Kusumaningrum',
        'pemberi_tugas_jabatan' => 'HC, GA & Finance Manager',
    ]);
});

it('surat SPD standalone menampilkan barcode/QR tanda tangan', function () {
    $this->actingAs($this->admin)
        ->get(route('spd.surat.print', $this->surat))
        ->assertStatus(200)
        ->assertSee('Scan untuk verifikasi', false)
        ->assertSee('<svg', false);
});

it('dokumen gabungan SPD + LPJ menampilkan barcode/QR tanda tangan', function () {
    $lpj = LpjPenggantian::create([
        'surat_spd_id' => $this->surat->id,
        'user_id'      => $this->admin->id,
        'vehicle_id'   => $this->vehicle->id,
        'nomor_polisi' => 'D 1234 TST',
        'nama'         => 'Driver Test',
        'jabatan'      => 'Driver',
        'uraian'       => 'Perjalanan dinas uji',
        'status'       => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->get(route('spd.laporan.export', $lpj))
        ->assertStatus(200)
        ->assertSee('Scan untuk verifikasi', false)
        ->assertSee('<svg', false);
});
