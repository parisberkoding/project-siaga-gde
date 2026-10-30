<?php

use App\Models\Role;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleChecking;
use App\Models\VehicleCheckingDetail;
use App\Models\VehiclePartsReplacement;
use App\Models\VehiclePartsReplacementTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * BUG-VPR-001 — Admin GA harus bisa menutup VPR berstatus "sedang service"
 * dari halaman detail, beserta input biaya aktual.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->ga = User::factory()->create([
        'role' => 'general_affairs',
        'nama_lengkap' => 'Admin GA VPR',
    ]);
    $this->ga->roles()->attach(
        Role::where('code', 'general_affairs')->firstOrFail()->id,
        ['is_primary' => true]
    );

    $this->driver = User::factory()->create(['role' => 'requester']);

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 9001 VPR',
        'is_active' => true,
        'is_approved' => true,
    ]);

    $checking = VehicleChecking::create([
        'checking_code' => 'CHK-VPR-TEST',
        'vehicle_id' => $this->vehicle->id,
        'nopol' => $this->vehicle->consumerial_code,
        'revision' => 0,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'generated_by' => $this->ga->id,
        'status' => 'active',
    ]);

    $detail = VehicleCheckingDetail::create([
        'checking_detail_code' => 'CD-VPR-TEST',
        'vehicle_checking_id' => $checking->id,
        'type' => 'routine',
        'sub_type' => 'daily',
        'dispatcher_id' => $this->ga->id,
        'current_driver_id' => $this->driver->id,
        'checked_at' => now(),
        'driver_acknowledged' => false,
    ]);

    $this->replacement = VehiclePartsReplacement::create([
        'vpr_code' => 'VPR-B9001VPR-2026-001',
        'vehicle_checking_detail_id' => $detail->id,
        'vehicle_id' => $this->vehicle->id,
        'nopol' => $this->vehicle->consumerial_code,
        'dispatcher_id' => $this->ga->id,
        'last_driver_id' => $this->driver->id,
        'status' => 'in_service',
        'scheduled_date' => now()->subDay(),
        'service_location' => 'Bengkel Uji',
    ]);

    $this->transaction = VehiclePartsReplacementTransaction::create([
        'vprt_code' => 'VPRT-B9001VPR-2026-001',
        'vehicle_parts_replacement_id' => $this->replacement->id,
        'vpr_code' => $this->replacement->vpr_code,
        'driver_id' => $this->driver->id,
        'service_location' => 'Bengkel Uji',
        'service_date' => now()->subDay(),
        'total_amount' => 500000,
        'proof_photo' => 'vpr/proof-uji.jpg',
        'deadline_date' => now()->addDays(2),
        'reported_at' => now(),
    ]);
});

it('menampilkan tombol Tandai Selesai beserta input biaya aktual', function () {
    $this->actingAs($this->ga)
        ->get(route('admin.replacement-approval.show', $this->replacement->vpr_code))
        ->assertOk()
        ->assertSee('Tandai Selesai')
        ->assertSee('name="actual_cost"', false)
        ->assertSee('500000', false);
});

it('menyimpan biaya aktual lalu menutup VPR', function () {
    $this->actingAs($this->ga)
        ->post(route('admin.replacement-approval.complete', $this->replacement->vpr_code), [
            'actual_cost' => 650000,
        ])
        ->assertRedirect(route('admin.replacement-approval.show', $this->replacement->vpr_code))
        ->assertSessionHas('success');

    $replacement = $this->replacement->fresh();

    expect($replacement->status)->toBe('completed')
        ->and($replacement->completed_at)->not->toBeNull()
        ->and((float) $this->transaction->fresh()->total_amount)->toBe(650000.0);
});

it('menolak biaya aktual negatif dan tidak mengubah status', function () {
    $this->actingAs($this->ga)
        ->from(route('admin.replacement-approval.show', $this->replacement->vpr_code))
        ->post(route('admin.replacement-approval.complete', $this->replacement->vpr_code), [
            'actual_cost' => -10,
        ])
        ->assertSessionHasErrors('actual_cost');

    expect($this->replacement->fresh()->status)->toBe('in_service');
});
