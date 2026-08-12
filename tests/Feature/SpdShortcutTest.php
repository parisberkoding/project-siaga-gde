<?php

use App\Models\EmployeeDefault;
use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    $this->superadmin->roles()->attach($role->id, ['is_primary' => true]);

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'is_active' => true,
        'consumerial_type' => 'Kendaraan',
    ]);
});

it('shortcut SPD: pengemudi manual (tanpa akun) tetap bisa dibuat', function () {
    $this->actingAs($this->superadmin)
        ->post(route('surat.superadmin.store-shortcut'), [
            'pemberi_tugas_level' => 'manager',
            'pemberi_tugas_nama' => 'Budi Manager',
            'pemberi_tugas_jabatan' => 'Operations Manager',
            'pengemudi_nama' => 'Pengemudi Lepas',
            'tempat_tujuan' => 'Bandung',
            'melayani_bidang' => 'Operasional',
            'jumlah_pemakai' => 1,
            'vehicle_id' => $this->vehicle->id,
            'tanggal_berangkat' => now()->addDay()->format('Y-m-d'),
            'tanggal_kembali' => now()->addDays(3)->format('Y-m-d'),
            'type' => 'biasa',
            'budgets' => [
                ['tanggal' => now()->addDay()->format('Y-m-d'), 'nominal' => 100000],
            ],
        ])
        ->assertRedirect();

    $surat = SuratSpd::latest('id')->first();
    expect($surat)->not->toBeNull();
    expect($surat->pengemudi_nama)->toBe('Pengemudi Lepas');
    expect($surat->pemberi_tugas_nama)->toBe('Budi Manager');
    expect($surat->pemberi_tugas_jabatan)->toBe('Operations Manager');
    expect($surat->status)->toBe('approved');
    expect($surat->user_id)->toBeNull();
    expect($surat->employee_default_id)->toBeNull();
});

it('shortcut SPD: pengemudi dari Master Pekerja terlink ke employee_default', function () {
    $emp = EmployeeDefault::create([
        'employee_name' => 'Supir Andi',
        'position' => 'Driver',
        'is_active' => true,
    ]);

    $this->actingAs($this->superadmin)
        ->post(route('surat.superadmin.store-shortcut'), [
            'pemberi_tugas_level' => 'assistant_manager',
            'pemberi_tugas_nama' => 'Siti AM',
            'pemberi_tugas_jabatan' => 'Assistant Manager GA',
            'pengemudi_employee_id' => $emp->id,
            'pengemudi_nama' => 'Supir Andi',
            'tempat_tujuan' => 'Jakarta',
            'melayani_bidang' => 'Operasional',
            'jumlah_pemakai' => 1,
            'vehicle_id' => $this->vehicle->id,
            'tanggal_berangkat' => now()->addDay()->format('Y-m-d'),
            'tanggal_kembali' => now()->addDays(2)->format('Y-m-d'),
            'type' => 'biasa',
        ])
        ->assertRedirect();

    $surat = SuratSpd::latest('id')->first();
    expect($surat->employee_default_id)->toBe($emp->id);
    expect($surat->pengemudi_nama)->toBe('Supir Andi');
    expect($surat->user_id)->toBeNull(); // emp tanpa akun
});

it('shortcut SPD: menolak tanpa nama pemberi tugas', function () {
    $this->actingAs($this->superadmin)
        ->post(route('surat.superadmin.store-shortcut'), [
            'pemberi_tugas_level' => 'manager',
            'pemberi_tugas_nama' => '',
            'pemberi_tugas_jabatan' => 'Manager',
            'pengemudi_nama' => 'Pengemudi',
            'tempat_tujuan' => 'Bandung',
            'melayani_bidang' => 'Operasional',
            'jumlah_pemakai' => 1,
            'vehicle_id' => $this->vehicle->id,
            'tanggal_berangkat' => now()->addDay()->format('Y-m-d'),
            'tanggal_kembali' => now()->addDays(2)->format('Y-m-d'),
            'type' => 'biasa',
        ])
        ->assertSessionHasErrors('pemberi_tugas_nama');
});

it('menyajikan form shortcut dengan opsi Master Pekerja', function () {
    EmployeeDefault::create(['employee_name' => 'Pekerja Shortcut', 'position' => 'Driver', 'is_active' => true]);
    EmployeeDefault::create(['employee_name' => 'Manager Shortcut', 'position' => 'Operations Manager', 'is_active' => true]);

    $this->actingAs($this->superadmin)
        ->get(route('surat.superadmin.create-shortcut'))
        ->assertStatus(200)
        ->assertSee('Pekerja Shortcut', false)
        ->assertSee('Manager Shortcut', false);
});
