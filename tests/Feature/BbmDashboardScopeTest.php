<?php

use App\Models\Permission;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * BUG-BBM-005 / BUG-RBAC-001 / BUG-BBM-006 / BUG-BBM-007 / BUG-UI-001.
 *
 * Dashboard BBM adalah dashboard analitik tingkat unit:
 *  - user ber-unit hanya melihat data unitnya (global/Pusat melihat semua),
 *  - driver/requester tidak boleh masuk (dialihkan ke dashboard driver),
 *  - kartu ringkasan mengikuti start_date/end_date eksplisit,
 *  - tombol Export CSV & Trip Analysis disembunyikan tanpa permission.
 */
beforeEach(function () {
    $this->seed(RbacSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->seed(ModuleSeeder::class);

    $this->unitPatuha = Unit::where('code', 'PATUHA')->firstOrFail();
    $this->unitDieng = Unit::where('code', 'DIENG')->firstOrFail();

    $this->buatUser = function (string $roleCode, ?Unit $unit, array $attributes = []): User {
        $user = User::factory()->create(array_merge([
            'role' => $roleCode,
            'unit_id' => $unit?->id,
        ], $attributes));

        $user->roles()->attach(
            Role::where('code', $roleCode)->firstOrFail()->id,
            ['is_primary' => true]
        );

        return $user->fresh();
    };

    $this->buatKendaraan = function (string $code, string $name, Unit $unit): VehicleAndToolsConsumers {
        return VehicleAndToolsConsumers::factory()->create([
            'consumerial_code' => $code,
            'consumerial_name' => $name,
            'unit_id' => $unit->id,
            'is_approved' => true,
        ]);
    };

    $this->buatPermohonan = function (array $attributes): Permohonan {
        return Permohonan::factory()->create(array_merge([
            'status' => 'approved',
            'bill_amounts' => 200000,
            'request_date' => now(),
        ], $attributes));
    };
});

it('membatasi dashboard BBM ke unit user: Admin GA tidak melihat driver/kendaraan unit lain', function () {
    $adminPatuha = ($this->buatUser)('general_affairs', $this->unitPatuha, [
        'nama_lengkap' => 'Admin GA Patuha',
    ]);

    $driverPatuha = ($this->buatUser)('requester', $this->unitPatuha, [
        'nama_lengkap' => 'Driver Patuha Terlihat',
    ]);
    $driverDieng = ($this->buatUser)('requester', $this->unitDieng, [
        'nama_lengkap' => 'Driver Dieng Rahasia',
    ]);

    ($this->buatPermohonan)([
        'request_number' => 'REQ-PATUHA-SCOPED',
        'requester_id' => $driverPatuha->id,
        'consumerial_tools_id' => ($this->buatKendaraan)('B-PTHU-SCOPED', 'Kendaraan Patuha', $this->unitPatuha)->id,
        'unit_id' => $this->unitPatuha->id,
    ]);

    ($this->buatPermohonan)([
        'request_number' => 'REQ-DIENG-SCOPED',
        'requester_id' => $driverDieng->id,
        'consumerial_tools_id' => ($this->buatKendaraan)('B-DIENG-RAHASIA', 'Kendaraan Dieng', $this->unitDieng)->id,
        'unit_id' => $this->unitDieng->id,
    ]);

    $this->actingAs($adminPatuha)
        ->get(route('modules.dashboard.bbm'))
        ->assertOk()
        ->assertSee('REQ-PATUHA-SCOPED')
        ->assertSee('Driver Patuha Terlihat')
        ->assertSee('B-PTHU-SCOPED')
        ->assertDontSee('REQ-DIENG-SCOPED')
        ->assertDontSee('Driver Dieng Rahasia')
        ->assertDontSee('B-DIENG-RAHASIA');
});

it('user global tetap melihat seluruh unit pada dashboard BBM', function () {
    $superadmin = ($this->buatUser)('superadmin', null, ['nama_lengkap' => 'Superadmin Pusat']);

    $driverDieng = ($this->buatUser)('requester', $this->unitDieng, [
        'nama_lengkap' => 'Driver Dieng Global',
    ]);

    ($this->buatPermohonan)([
        'request_number' => 'REQ-DIENG-GLOBAL',
        'requester_id' => $driverDieng->id,
        'consumerial_tools_id' => ($this->buatKendaraan)('B-DIENG-GLOBAL', 'Kendaraan Dieng Global', $this->unitDieng)->id,
        'unit_id' => $this->unitDieng->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('modules.dashboard.bbm'))
        ->assertOk()
        ->assertSee('REQ-DIENG-GLOBAL')
        ->assertSee('Driver Dieng Global')
        ->assertSee('B-DIENG-GLOBAL');
});

it('driver yang membuka dashboard BBM dialihkan ke dashboard driver', function () {
    $driver = ($this->buatUser)('requester', $this->unitPatuha, [
        'nama_lengkap' => 'Driver Biasa',
    ]);

    $this->actingAs($driver)
        ->get(route('modules.dashboard.bbm'))
        ->assertRedirect(route('driver.dashboard'));
});

it('kartu ringkasan mengikuti start_date/end_date eksplisit, bukan bulan berjalan', function () {
    $superadmin = ($this->buatUser)('superadmin', null, ['nama_lengkap' => 'Superadmin Pusat']);

    // Transaksi di bulan berjalan tidak boleh ikut terhitung saat user
    // memfilter bulan lain yang kosong.
    ($this->buatPermohonan)([
        'request_number' => 'REQ-BULAN-BERJALAN',
        'requester_id' => $superadmin->id,
        'consumerial_tools_id' => ($this->buatKendaraan)('B-NOW-1', 'Kendaraan Sekarang', $this->unitPatuha)->id,
        'unit_id' => $this->unitPatuha->id,
        'bill_amounts' => 777000,
    ]);

    $response = $this->actingAs($superadmin)
        ->get(route('modules.dashboard.bbm', [
            'start_date' => '2000-06-01',
            'end_date' => '2000-06-30',
        ]))
        ->assertOk()
        ->assertSee('01 Jun 2000 – 30 Jun 2000');

    $response->assertViewHas('stats', function (array $stats) {
        return (int) $stats['totalTransactions'] === 0
            && (float) $stats['totalExpense'] === 0.0
            && (float) $stats['avgRequestValue'] === 0.0
            && $stats['periodLabel'] === '01 Jun 2000 – 30 Jun 2000'
            && $stats['startDate'] === '2000-06-01'
            && $stats['endDate'] === '2000-06-30';
    });
});

it('tautan periode tetap berada di dashboard modul BBM', function () {
    $adminPatuha = ($this->buatUser)('general_affairs', $this->unitPatuha, [
        'nama_lengkap' => 'Admin GA Navigasi',
    ]);

    $this->actingAs($adminPatuha)
        ->get(route('modules.dashboard.bbm'))
        ->assertOk()
        ->assertSee(route('modules.dashboard.bbm', ['period' => 'day']), false)
        ->assertSee(route('modules.dashboard.bbm', ['period' => 'month']), false)
        ->assertSee(route('modules.dashboard.bbm', ['period' => 'year']), false)
        ->assertDontSee(route('superadmin.dashboard.bisnis', ['period' => 'day']), false);
});

it('menyembunyikan Export CSV dan Trip Analysis saat user tidak punya permission', function () {
    $role = Role::where('code', 'general_affairs')->firstOrFail();
    $role->permissions()->detach(
        Permission::whereIn('code', ['bbm.export', 'vehicle.report'])->pluck('id')->all()
    );

    $adminPatuha = ($this->buatUser)('general_affairs', $this->unitPatuha, [
        'nama_lengkap' => 'Admin GA Tanpa Export',
    ]);

    $this->actingAs($adminPatuha)
        ->get(route('modules.dashboard.bbm'))
        ->assertOk()
        ->assertSee('Tabel Pivot: Data Lengkap')
        ->assertDontSee('Export CSV')
        ->assertDontSee('Trip Analysis');
});
