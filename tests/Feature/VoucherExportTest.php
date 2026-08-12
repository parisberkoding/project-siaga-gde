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
