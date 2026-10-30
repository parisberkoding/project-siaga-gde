<?php

use App\Models\Role;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleChecking;
use App\Models\VehicleCheckingDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * BUG-CHK-004 — kartu riwayat scan QR memakai kolom `nama_lengkap` pada tabel
 * users (tabel users tidak punya kolom `name`, sehingga pemanggilan `->name`
 * menghasilkan nama kosong).
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->admin = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    $this->admin->roles()->attach($role->id, ['is_primary' => true]);
});

it('menampilkan nama dispatcher dan driver dari kolom nama_lengkap pada kartu scan', function () {
    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'is_active'        => true,
        'consumerial_type' => 'Kendaraan',
        'consumerial_code' => 'B 9001 QA',
    ]);

    $header = VehicleChecking::create([
        'checking_code' => VehicleChecking::generateCode($vehicle->consumerial_code, now()),
        'vehicle_id'    => $vehicle->id,
        'nopol'         => $vehicle->consumerial_code,
        'revision'      => 0,
        'period_start'  => now()->startOfMonth()->toDateString(),
        'period_end'    => now()->endOfMonth()->toDateString(),
        'generated_by'  => $this->admin->id,
        'status'        => 'active',
    ]);

    $dispatcher = User::factory()->create([
        'role'         => 'dispatcher',
        'nama_lengkap' => 'Dispatcher Scan QA',
    ]);
    $driver = User::factory()->create([
        'role'         => 'requester',
        'nama_lengkap' => 'Driver Scan QA',
    ]);

    $detail = VehicleCheckingDetail::create([
        'checking_detail_code' => VehicleCheckingDetail::generateCode($header),
        'vehicle_checking_id'  => $header->id,
        'type'                 => 'routine',
        'sub_type'             => 'daily',
        'dispatcher_id'        => $dispatcher->id,
        'current_driver_id'    => $driver->id,
        'checked_at'           => now(),
        'driver_acknowledged'  => false,
    ]);

    $this->actingAs($this->admin);

    $html = view('dispatcher.barcode-scan._checking-card', ['detail' => $detail->fresh()])->render();

    expect($html)->toContain('Dispatcher Scan QA')
        ->and($html)->toContain('Driver Scan QA');
});
