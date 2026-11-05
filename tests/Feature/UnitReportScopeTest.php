<?php

use App\Models\Role;
use App\Models\EmployeeDefault;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleChecking;
use App\Models\VehicleCheckingDetail;
use App\Models\VehiclePartsReplacement;
use App\Models\VehiclePartsReplacementTransaction;
use Database\Seeders\RbacSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Matriks akses data berbasis unit:
 *   - superadmin unit (bukan Pusat)  -> hanya data unitnya
 *   - admin Pusat (role non-superadmin)-> hanya data unit Pusat
 *   - superadmin Pusat (is_central)   -> seluruh unit
 *
 * Fokus test: halaman laporan/administrasi kendaraan & service yang dulu
 * query-nya belum memakai scope unit.
 */
beforeEach(function () {
    $this->seed(RbacSeeder::class);
    $this->seed(UnitSeeder::class);

    $this->unitPatuha = Unit::where('code', 'PATUHA')->firstOrFail();
    $this->unitDieng  = Unit::where('code', 'DIENG')->firstOrFail();
    $this->unitPusat  = Unit::where('code', 'PUSAT')->firstOrFail();

    $makeUser = function (string $roleCode, ?int $unitId): User {
        $user = User::factory()->create([
            'role'    => $roleCode,
            'unit_id' => $unitId,
        ]);

        $user->roles()->attach(
            Role::where('code', $roleCode)->firstOrFail()->id,
            ['is_primary' => true]
        );

        return $user;
    };

    $this->superPatuha = $makeUser('superadmin', $this->unitPatuha->id);
    $this->superPusat  = $makeUser('superadmin', $this->unitPusat->id);
    $this->adminPusat  = $makeUser('general_affairs', $this->unitPusat->id);
    $this->dispatcher  = $makeUser('dispatcher', $this->unitPatuha->id);

    $this->vehicleP = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 8001 PTH',
        'consumerial_name' => 'Kendaraan Patuha',
        'unit_id'          => $this->unitPatuha->id,
    ]);
    $this->vehicleD = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'D 8002 DNG',
        'consumerial_name' => 'Kendaraan Dieng',
        'unit_id'          => $this->unitDieng->id,
    ]);
    $this->vehicleC = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 8003 PST',
        'consumerial_name' => 'Kendaraan Pusat',
        'unit_id'          => $this->unitPusat->id,
    ]);

    $driver = $makeUser('requester', $this->unitPatuha->id);

    // Satu VPR + transaksi service per unit, plus satu detail pengecekan harian.
    $this->vprCodes = [];
    $this->vprtCodes = [];

    foreach ([
        'PTH' => [$this->vehicleP, $this->unitPatuha->id],
        'DNG' => [$this->vehicleD, $this->unitDieng->id],
        'PST' => [$this->vehicleC, $this->unitPusat->id],
    ] as $suffix => [$vehicle, $unitId]) {
        $header = VehicleChecking::create([
            'checking_code' => "CHK-{$suffix}-2026-11",
            'vehicle_id'    => $vehicle->id,
            'nopol'         => $vehicle->consumerial_code,
            'revision'      => 0,
            'period_start'  => now()->startOfMonth()->toDateString(),
            'period_end'    => now()->endOfMonth()->toDateString(),
            'generated_by'  => $this->superPusat->id,
            'status'        => 'active',
        ]);

        $detail = VehicleCheckingDetail::create([
            'checking_detail_code' => "CHK-{$suffix}-2026-11-001",
            'vehicle_checking_id'  => $header->id,
            'type'                 => 'routine',
            'sub_type'             => 'daily',
            'dispatcher_id'        => $this->dispatcher->id,
            'current_driver_id'    => $driver->id,
            'checked_at'           => now(),
            'driver_acknowledged'  => false,
        ]);

        $vpr = VehiclePartsReplacement::create([
            'vpr_code'                   => "VPR-{$suffix}-2026-11-001",
            'vehicle_checking_detail_id' => $detail->id,
            'vehicle_id'                 => $vehicle->id,
            'nopol'                      => $vehicle->consumerial_code,
            'dispatcher_id'              => $this->dispatcher->id,
            'last_driver_id'             => $driver->id,
            'status'                     => 'pending',
        ]);

        VehiclePartsReplacementTransaction::create([
            'vprt_code'                  => "VPRT-{$suffix}-2026-11-001",
            'vehicle_parts_replacement_id' => $vpr->id,
            'vpr_code'                   => $vpr->vpr_code,
            'driver_id'                  => $driver->id,
            'service_location'           => "Bengkel {$suffix}",
            'service_date'               => now()->toDateString(),
            'total_amount'               => 500000,
            'proof_photo'                => "proof-{$suffix}.jpg",
            'reported_at'                => now(),
        ]);

        $this->vprCodes[$suffix]  = $vpr->vpr_code;
        $this->vprtCodes[$suffix] = "VPRT-{$suffix}-2026-11-001";
    }
});

it('superadmin unit hanya melihat laporan kendaraan & service unitnya', function () {
    $this->actingAs($this->superPatuha);

    $this->get(route('superadmin.vehicle-health.index'))
        ->assertOk()
        ->assertSee('B 8001 PTH')
        ->assertDontSee('D 8002 DNG')
        ->assertDontSee('B 8003 PST');

    $this->get(route('superadmin.financial-report.index'))
        ->assertOk()
        ->assertSee($this->vprtCodes['PTH'])
        ->assertDontSee($this->vprtCodes['DNG'])
        ->assertDontSee($this->vprtCodes['PST']);

    $this->get(route('superadmin.transaction-report.index'))
        ->assertOk()
        ->assertSee($this->vprtCodes['PTH'])
        ->assertDontSee($this->vprtCodes['DNG'])
        ->assertDontSee($this->vprtCodes['PST']);

    $this->get(route('admin.replacement-approval.index'))
        ->assertOk()
        ->assertSee($this->vprCodes['PTH'])
        ->assertDontSee($this->vprCodes['DNG'])
        ->assertDontSee($this->vprCodes['PST']);

    $this->get(route('dispatcher.daily-check.index'))
        ->assertOk()
        ->assertSee('CHK-PTH-2026-11-001')
        ->assertDontSee('CHK-DNG-2026-11-001')
        ->assertDontSee('CHK-PST-2026-11-001');
});

it('superadmin pusat melihat data seluruh unit', function () {
    $this->actingAs($this->superPusat);

    foreach (['PTH', 'DNG', 'PST'] as $suffix) {
        $this->get(route('superadmin.vehicle-health.index'))
            ->assertOk()
            ->assertSee(match ($suffix) {
                'PTH' => 'B 8001 PTH',
                'DNG' => 'D 8002 DNG',
                'PST' => 'B 8003 PST',
            });
    }

    $this->get(route('superadmin.financial-report.index'))
        ->assertOk()
        ->assertSee($this->vprtCodes['PTH'])
        ->assertSee($this->vprtCodes['DNG'])
        ->assertSee($this->vprtCodes['PST']);

    $this->get(route('superadmin.transaction-report.index'))
        ->assertOk()
        ->assertSee($this->vprtCodes['PTH'])
        ->assertSee($this->vprtCodes['DNG'])
        ->assertSee($this->vprtCodes['PST']);

    $this->get(route('admin.replacement-approval.index'))
        ->assertOk()
        ->assertSee($this->vprCodes['PTH'])
        ->assertSee($this->vprCodes['DNG'])
        ->assertSee($this->vprCodes['PST']);
});

it('admin pusat hanya melihat data unit pusat', function () {
    $this->actingAs($this->adminPusat);

    $this->get(route('admin.replacement-approval.index'))
        ->assertOk()
        ->assertSee($this->vprCodes['PST'])
        ->assertDontSee($this->vprCodes['PTH'])
        ->assertDontSee($this->vprCodes['DNG']);

    // Role Pusat non-superadmin tidak punya hak laporan kendaraan.
    $this->get(route('superadmin.vehicle-health.index'))->assertForbidden();
    $this->get(route('superadmin.financial-report.index'))->assertForbidden();
});

it('detail VPR & kesehatan kendaraan unit lain ditolak 404', function () {
    $this->actingAs($this->superPatuha)
        ->get(route('admin.replacement-approval.show', $this->vprCodes['DNG']))
        ->assertNotFound();

    $this->actingAs($this->superPatuha)
        ->get(route('superadmin.vehicle-health.show', $this->vehicleD->consumerial_code))
        ->assertNotFound();

    $this->actingAs($this->superPatuha)
        ->get(route('admin.replacement-approval.show', $this->vprCodes['PTH']))
        ->assertOk();
});

it('menolak akses/edit kendaraan unit lain lewat URL langsung', function () {
    $this->actingAs($this->superPatuha);

    // Baca & edit master kendaraan unit lain.
    $this->get(route('superadmin.vehicle.detail', $this->vehicleD->id))->assertNotFound();
    $this->get(route('vehicles.edit', $this->vehicleD->id))->assertNotFound();
    $this->get(route('superadmin.vehicle.detail.edit', $this->vehicleD->id))->assertNotFound();

    // Tulis: update, hapus, reject kendaraan unit lain.
    $this->post(route('vehicles.update', $this->vehicleD->id), [])->assertNotFound();

    // Hapus & reject menangkap exception menjadi redirect; yang penting
    // kendaraan unit lain TIDAK ikut terhapus / berubah.
    $this->post(route('vehicles.delete', $this->vehicleD->id));
    $this->post(route('superadmin.vehicle.reject', $this->vehicleD->id), []);

    expect(VehicleAndToolsConsumers::withTrashed()->find($this->vehicleD->id)->trashed())
        ->toBeFalse();

    // Detail pengecekan unit lain.
    $this->get(route('dispatcher.daily-check.show', 'CHK-DNG-2026-11-001'))->assertNotFound();

    // Unit sendiri tetap bisa diakses.
    $this->get(route('superadmin.vehicle.detail', $this->vehicleP->id))->assertOk();
    $this->get(route('dispatcher.daily-check.show', 'CHK-PTH-2026-11-001'))->assertOk();
});

it('export laporan keuangan terbatas unit dan tidak error', function () {
    $this->actingAs($this->superPatuha);

    $this->get(route('superadmin.financial-report.export', [
        'month' => now()->format('Y-m'),
        'format' => 'excel',
    ]))->assertOk();

    $this->get(route('superadmin.financial-report.export', [
        'month' => now()->format('Y-m'),
        'format' => 'pdf',
    ]))->assertOk();
});

it('data mart unit lain ditolak untuk superadmin unit', function () {
    $this->actingAs($this->superPatuha)
        ->get(route('api.datamart.context', $this->unitDieng->id))
        ->assertForbidden();

    // Superadmin Pusat tetap boleh membangun konteks unit mana pun.
    $this->actingAs($this->superPusat)
        ->get(route('api.datamart.context', $this->unitPatuha->id))
        ->assertOk();
});

it('dropdown pemberi tugas & pengemudi SPD hanya berisi unit user', function () {
    $sitePatuha = Site::where('code', 'PTH')->firstOrFail();
    $siteDieng  = Site::where('code', 'Dieng')->firstOrFail();

    EmployeeDefault::create([
        'employee_name'   => 'Manager Patuha',
        'position'        => 'Operations Manager',
        'default_site_id' => $sitePatuha->id,
        'unit_id'         => $this->unitPatuha->id,
        'is_active'       => true,
    ]);
    EmployeeDefault::create([
        'employee_name'   => 'Manager Dieng',
        'position'        => 'Operations Manager',
        'default_site_id' => $siteDieng->id,
        'unit_id'         => $this->unitDieng->id,
        'is_active'       => true,
    ]);

    $this->actingAs($this->superPatuha)
        ->get(route('surat.superadmin.create-shortcut'))
        ->assertOk()
        ->assertSee('Manager Patuha')
        ->assertDontSee('Manager Dieng');
});

it('halaman master harga BBM hanya menampilkan kendaraan unit user', function () {
    $this->actingAs($this->superPatuha)
        ->get(route('master-gasoline.index'))
        ->assertOk()
        ->assertSee('B 8001 PTH')
        ->assertDontSee('D 8002 DNG')
        ->assertDontSee('B 8003 PST');
});

it('ringkasan laporan keuangan menghitung semua transaksi, bukan hanya halaman pertama', function () {
    $vpr = VehiclePartsReplacement::where('vpr_code', $this->vprCodes['PTH'])->firstOrFail();
    $driver = User::where('role', 'requester')->firstOrFail();

    // 24 transaksi tambahan → total 25 baris (> 20 per halaman).
    for ($i = 1; $i <= 24; $i++) {
        VehiclePartsReplacementTransaction::create([
            'vprt_code'                    => "VPRT-PTH-BULK-{$i}",
            'vehicle_parts_replacement_id' => $vpr->id,
            'vpr_code'                     => $vpr->vpr_code,
            'driver_id'                    => $driver->id,
            'service_location'             => 'Bengkel Bulk',
            'service_date'                 => now()->toDateString(),
            'total_amount'                 => 500000,
            'proof_photo'                  => "bulk-{$i}.jpg",
            'reported_at'                  => now(),
        ]);
    }

    // 25 x Rp 500.000 = Rp 12.500.000 (bukan Rp 10.000.000 milik 20 baris pertama).
    $this->actingAs($this->superPatuha)
        ->get(route('superadmin.financial-report.index'))
        ->assertOk()
        ->assertSee('12.500.000');
});
