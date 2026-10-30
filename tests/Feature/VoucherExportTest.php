<?php

use App\Models\Approved_Request;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->admin = User::factory()->create(['role' => 'admin']);
    $role = Role::where('code', 'general_affairs')->first();
    $this->admin->roles()->attach($role->id, ['is_primary' => true]);

    // Kendaraan + permohonan + voucher kemarin
    $vehicle = VehicleAndToolsConsumers::factory()->create();
    $requester = User::factory()->create(['role' => 'requester']);

    $permohonan = Permohonan::factory()->create([
        'requester_id' => $requester->id,
        'consumerial_tools_id' => $vehicle->id,
        'status' => 'approved',
        'request_date' => now()->subDay(),
    ]);

    $this->voucher = Approved_Request::create([
        'permohonan_id' => $permohonan->id,
        'approval_date' => now()->subDay(),
        'voucher_code' => 'BBMGDE-TEST01-'.now()->format('dmy'),
        'valid_until' => now()->addDay(),
        'approved_amount' => 150000,
        'authorizer_id' => $this->admin->id,
        'status' => 'approved',
    ]);
});

it('menyajikan list voucher kemarin via AJAX', function () {
    $this->actingAs($this->admin)
        ->getJson(route('admin.vouchers.list', ['date' => now()->subDay()->toDateString()]))
        ->assertStatus(200)
        ->assertJsonPath('count', 1)
        ->assertJsonPath('data.0.voucher_code', $this->voucher->voucher_code);
});

it('menyembunyikan list voucher dari role tanpa akses', function () {
    $requester = User::factory()->create(['role' => 'requester']);
    $role = Role::where('code', 'requester')->first();
    $requester->roles()->attach($role->id, ['is_primary' => true]);

    $this->actingAs($requester)
        ->getJson(route('admin.vouchers.list'))
        ->assertForbidden();
});

it('mengekspor voucher terpilih ke xlsx', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.vouchers.export'), [
            'ids' => [$this->voucher->id],
        ])
        ->assertStatus(200)
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('mengekspor semua voucher per tanggal ke xlsx', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.vouchers.export'), [
            'date' => now()->subDay()->toDateString(),
        ])
        ->assertStatus(200)
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('menolak export tanpa data voucher', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.vouchers.export'), [
            'date' => now()->subYears(5)->toDateString(), // tanggal kosong
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('membatasi export voucher ke unit pengguna (tidak bocor ke unit lain)', function () {
    $unitA = \App\Models\Unit::create(['code' => 'P2-UA', 'name' => 'Unit A', 'is_active' => true]);
    $unitB = \App\Models\Unit::create(['code' => 'P2-UB', 'name' => 'Unit B', 'is_active' => true]);

    // Admin GA unit A; voucher pada beforeEach menjadi milik unit A.
    $this->admin->update(['unit_id' => $unitA->id]);
    $this->voucher->update(['unit_id' => $unitA->id]);
    $this->voucher->permohonan->update(['unit_id' => $unitA->id]);

    // Voucher unit B pada tanggal lain.
    $vehicleB = VehicleAndToolsConsumers::factory()->create(['unit_id' => $unitB->id]);
    $requesterB = User::factory()->create(['role' => 'requester', 'unit_id' => $unitB->id]);
    $permohonanB = Permohonan::factory()->create([
        'requester_id'         => $requesterB->id,
        'consumerial_tools_id' => $vehicleB->id,
        'status'               => 'approved',
        'request_date'         => now()->subDays(2),
        'unit_id'              => $unitB->id,
    ]);
    $voucherB = Approved_Request::create([
        'permohonan_id'  => $permohonanB->id,
        'unit_id'        => $unitB->id,
        'approval_date'  => now()->subDays(2),
        'voucher_code'   => 'BBMGDE-TEST02-'.now()->format('dmy'),
        'valid_until'    => now()->addDay(),
        'approved_amount' => 200000,
        'authorizer_id'  => $this->admin->id,
        'status'         => 'approved',
    ]);

    // Export per-ID milik unit lain → tidak ada data (bukan 200 dengan bocor).
    $this->actingAs($this->admin)
        ->post(route('admin.vouchers.export'), ['ids' => [$voucherB->id]])
        ->assertRedirect()
        ->assertSessionHas('error');

    // Export per-tanggal yang hanya berisi voucher unit lain → tidak ada data.
    $this->actingAs($this->admin)
        ->post(route('admin.vouchers.export'), ['date' => now()->subDays(2)->toDateString()])
        ->assertRedirect()
        ->assertSessionHas('error');

    // Voucher unit sendiri tetap bisa diexport.
    $this->actingAs($this->admin)
        ->post(route('admin.vouchers.export'), ['ids' => [$this->voucher->id]])
        ->assertStatus(200);
});
