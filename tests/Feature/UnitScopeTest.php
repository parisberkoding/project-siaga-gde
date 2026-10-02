<?php

use App\Models\Approved_Request;
use App\Models\EmployeeDefault;
use App\Models\LunchDistribution;
use App\Models\LunchForecast;
use App\Models\LunchGuestReservation;
use App\Models\LunchTransaction;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\Site;
use App\Models\SuratSpd;
use App\Models\Transaction_Proofs;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleDetail;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    // Site sebagian sudah di-seed oleh migration (PMU, Patuha, BCMP, Dieng, Pusat).
    // updateOrCreate agar idempotent & melengkapi site yang belum ada.
    $this->sitePTH   = Site::updateOrCreate(['code' => 'PTH'],    ['name' => 'Patuha',          'is_active' => true]);
    $this->sitePTHBC = Site::updateOrCreate(['code' => 'PTH-BC'], ['name' => 'Patuha Basecamp', 'is_active' => true]);
    $this->siteDieng = Site::updateOrCreate(['code' => 'Dieng'],  ['name' => 'Dieng',           'is_active' => true]);
    $this->sitePusat = Site::updateOrCreate(['code' => 'Pusat'],  ['name' => 'Kantor Pusat',    'is_active' => true]);

    $this->seed(\Database\Seeders\UnitSeeder::class);

    $this->unitPatuha = Unit::where('code', 'PATUHA')->first();
    $this->unitDieng  = Unit::where('code', 'DIENG')->first();

    $this->patuhaIds = [$this->sitePTH->id, $this->sitePTHBC->id];

    $this->ga = User::factory()->create([
        'role'    => 'general_affairs',
        'unit_id' => $this->unitPatuha->id,
    ]);
    $this->globalGa = User::factory()->create(['role' => 'general_affairs', 'unit_id' => null]);

    $role = Role::where('code', 'general_affairs')->first();
    $this->ga->roles()->attach($role->id, ['is_primary' => true]);
    $this->globalGa->roles()->attach($role->id, ['is_primary' => true]);

    $this->makeVehicle = function (string $code, int $siteId) {
        $vehicle = VehicleAndToolsConsumers::factory()->create([
            'consumerial_code' => $code,
            'is_approved'      => true,
            'unit_id'          => Site::find($siteId)?->unit_id,
        ]);
        VehicleDetail::factory()->create([
            'vehicle_and_tools_consumer_id' => $vehicle->id,
            'site_id'                       => $siteId,
        ]);

        return $vehicle;
    };
});

it('unitSiteIds mengembalikan site milik unit, null untuk user global', function () {
    expect($this->ga->hasUnitScope())->toBeTrue();
    expect($this->ga->unitSiteIds())
        ->toContain($this->sitePTH->id)
        ->toContain($this->sitePTHBC->id)
        ->not->toContain($this->siteDieng->id);

    expect($this->globalGa->hasUnitScope())->toBeFalse();
    expect($this->globalGa->unitSiteIds())->toBeNull();
});

it('scope kendaraan inUnit hanya menampilkan unit', function () {
    $vehiclePatuha = ($this->makeVehicle)('B-PTHU-1', $this->sitePTH->id);
    $vehicleDieng  = ($this->makeVehicle)('B-DIEN-1', $this->siteDieng->id);

    $ids = VehicleAndToolsConsumers::inUnit($this->unitPatuha->id)->pluck('id');

    expect($ids)->toContain($vehiclePatuha->id)->not->toContain($vehicleDieng->id);
    expect(VehicleAndToolsConsumers::inUnit(null)->pluck('id'))->toHaveCount(2);
});

it('scope permohonan inUnit mengikuti unit kendaraan', function () {
    $permP = Permohonan::factory()->create([
        'request_number'       => 'REQ-PTHU-1',
        'consumerial_tools_id' => ($this->makeVehicle)('B-PTHU-2', $this->sitePTH->id)->id,
        'unit_id'              => $this->unitPatuha->id,
    ]);
    $permD = Permohonan::factory()->create([
        'request_number'       => 'REQ-DIEN-1',
        'consumerial_tools_id' => ($this->makeVehicle)('B-DIEN-2', $this->siteDieng->id)->id,
        'unit_id'              => $this->unitDieng->id,
    ]);

    $ids = Permohonan::inUnit($this->unitPatuha->id)->pluck('id');

    expect($ids)->toContain($permP->id)->not->toContain($permD->id);
});

it('scope bukti transaksi inUnit mengikuti unit permohonan', function () {
    $permP = Permohonan::factory()->create([
        'consumerial_tools_id' => ($this->makeVehicle)('B-PTHU-3', $this->sitePTH->id)->id,
        'unit_id'              => $this->unitPatuha->id,
    ]);
    $permD = Permohonan::factory()->create([
        'consumerial_tools_id' => ($this->makeVehicle)('B-DIEN-3', $this->siteDieng->id)->id,
        'unit_id'              => $this->unitDieng->id,
    ]);

    $proofP = Transaction_Proofs::create([
        'transaction_id'    => 'TRX-PTHU-1',
        'req_id'            => $permP->id,
        'requester_id'      => $permP->requester_id,
        'purchase_datetime' => now(),
        'fuel_volume'       => 20,
        'struk_bbm_path'    => 'patuha.jpg',
    ]);
    $proofD = Transaction_Proofs::create([
        'transaction_id'    => 'TRX-DIEN-1',
        'req_id'            => $permD->id,
        'requester_id'      => $permD->requester_id,
        'purchase_datetime' => now(),
        'fuel_volume'       => 20,
        'struk_bbm_path'    => 'dieng.jpg',
    ]);

    $ids = Transaction_Proofs::inUnit($this->unitPatuha->id)->pluck('id');

    expect($ids)->toContain($proofP->id)->not->toContain($proofD->id);
});

it('scope surat SPD inUnit mengikuti unit user/pengemudi', function () {
    $userP = User::factory()->create(['role' => 'requester', 'unit_id' => $this->unitPatuha->id]);
    $userD = User::factory()->create(['role' => 'requester', 'unit_id' => $this->unitDieng->id]);

    $spdP = SuratSpd::factory()->create(['nomor_surat' => 'SPD-PTHU-1', 'user_id' => $userP->id, 'unit_id' => $this->unitPatuha->id]);
    $spdD = SuratSpd::factory()->create(['nomor_surat' => 'SPD-DIEN-1', 'user_id' => $userD->id, 'unit_id' => $this->unitDieng->id]);

    $ids = SuratSpd::inUnit($this->unitPatuha->id)->pluck('id');

    expect($ids)->toContain($spdP->id)->not->toContain($spdD->id);
});

it('scope surat SPD inUnit juga memakai unit master karyawan (employee_default)', function () {
    $userP = User::factory()->create(['role' => 'requester', 'unit_id' => $this->unitPatuha->id]);
    $employeeD = EmployeeDefault::create([
        'employee_name'   => 'Pengemudi Dieng',
        'default_site_id' => $this->siteDieng->id,
    ]);

    $spdP = SuratSpd::factory()->create(['nomor_surat' => 'SPD-PTHU-2', 'user_id' => $userP->id, 'unit_id' => $this->unitPatuha->id]);
    $spdE = SuratSpd::factory()->create([
        'nomor_surat'       => 'SPD-EMP-1',
        'user_id'           => null,
        'employee_default_id' => $employeeD->id,
        'unit_id'            => $this->unitDieng->id,
    ]);

    $ids = SuratSpd::inUnit($this->unitPatuha->id)->pluck('id');

    expect($ids)->toContain($spdP->id)->not->toContain($spdE->id);
});

it('scope approved request inUnit mengikuti unit permohonan', function () {
    $permP = Permohonan::factory()->create([
        'consumerial_tools_id' => ($this->makeVehicle)('B-PTHU-4', $this->sitePTH->id)->id,
        'unit_id'              => $this->unitPatuha->id,
    ]);
    $permD = Permohonan::factory()->create([
        'consumerial_tools_id' => ($this->makeVehicle)('B-DIEN-4', $this->siteDieng->id)->id,
        'unit_id'              => $this->unitDieng->id,
    ]);

    $voucherP = Approved_Request::create([
        'permohonan_id'  => $permP->id,
        'voucher_code'   => 'VCH-PTHU-1',
        'valid_until'    => now()->addDay(),
        'approved_amount' => 100000,
        'status'         => 'active',
        'authorizer_id'  => $this->ga->id,
        'unit_id'        => $this->unitPatuha->id,
    ]);
    $voucherD = Approved_Request::create([
        'permohonan_id'  => $permD->id,
        'voucher_code'   => 'VCH-DIEN-1',
        'valid_until'    => now()->addDay(),
        'approved_amount' => 100000,
        'status'         => 'active',
        'authorizer_id'  => $this->ga->id,
        'unit_id'        => $this->unitDieng->id,
    ]);

    $ids = Approved_Request::inUnit($this->unitPatuha->id)->pluck('id');

    expect($ids)->toContain($voucherP->id)->not->toContain($voucherD->id);
});

it('scope lunch inUnit membatasi karyawan & transaksi per site', function () {
    $empP = EmployeeDefault::create([
        'employee_name'   => 'Pegawai Patuha',
        'default_site_id' => $this->sitePTH->id,
        'is_active'       => true,
    ]);
    $empD = EmployeeDefault::create([
        'employee_name'   => 'Pegawai Dieng',
        'default_site_id' => $this->siteDieng->id,
        'is_active'       => true,
    ]);

    expect(EmployeeDefault::inUnit($this->patuhaIds)->pluck('id'))
        ->toContain($empP->id)->not->toContain($empD->id);

    $trxP = LunchTransaction::create([
        'transaction_date' => now()->toDateString(),
        'site_id'          => $this->sitePTH->id,
        'raw_alias'        => 'PEG-PATUHA-XYZ',
        'employee_name'    => 'Pegawai Patuha',
    ]);
    $trxD = LunchTransaction::create([
        'transaction_date' => now()->toDateString(),
        'site_id'          => $this->siteDieng->id,
        'raw_alias'        => 'PEG-DIENG-XYZ',
        'employee_name'    => 'Pegawai Dieng',
    ]);

    expect(LunchTransaction::inUnit($this->patuhaIds)->pluck('id'))
        ->toContain($trxP->id)->not->toContain($trxD->id);
});

it('scope forecast lunch inUnit memakai site predicted atau actual', function () {
    $empP = EmployeeDefault::create(['employee_name' => 'Pegawai Patuha', 'default_site_id' => $this->sitePTH->id]);
    $date = now()->toDateString();

    $predictedP = LunchForecast::create([
        'employee_default_id' => $empP->id,
        'lunch_date'          => $date,
        'predicted_site_id'   => $this->sitePTH->id,
    ]);
    $predictedD = LunchForecast::create([
        'employee_default_id' => $empP->id,
        'lunch_date'          => $date,
        'predicted_site_id'   => $this->siteDieng->id,
    ]);
    $actualOnly = LunchForecast::create([
        'employee_default_id' => $empP->id,
        'lunch_date'          => $date,
        'predicted_site_id'   => null,
        'actual_site_id'      => $this->sitePTHBC->id,
    ]);
    $movedIn = LunchForecast::create([
        'employee_default_id' => $empP->id,
        'lunch_date'          => $date,
        'predicted_site_id'   => $this->siteDieng->id,
        'actual_site_id'      => $this->sitePTH->id,
    ]);

    $ids = LunchForecast::inUnit($this->patuhaIds)->pluck('id');

    expect($ids)
        ->toContain($predictedP->id)
        ->toContain($actualOnly->id)
        ->toContain($movedIn->id)
        ->not->toContain($predictedD->id);
});

it('scope distribusi & reservasi tamu inUnit per site', function () {
    $distP = LunchDistribution::create([
        'distribution_date' => now()->toDateString(),
        'site_id'           => $this->sitePTH->id,
    ]);
    $distD = LunchDistribution::create([
        'distribution_date' => now()->toDateString(),
        'site_id'           => $this->siteDieng->id,
    ]);

    expect(LunchDistribution::inUnit($this->patuhaIds)->pluck('id'))
        ->toContain($distP->id)->not->toContain($distD->id);

    $guestP = LunchGuestReservation::create([
        'reservation_date' => now()->addDay()->toDateString(),
        'guest_name'       => 'Tamu Patuha',
        'institution'      => 'Vendor A',
        'guest_count'      => 2,
        'site_id'          => $this->sitePTH->id,
    ]);
    $guestD = LunchGuestReservation::create([
        'reservation_date' => now()->addDay()->toDateString(),
        'guest_name'       => 'Tamu Dieng',
        'institution'      => 'Vendor B',
        'guest_count'      => 2,
        'site_id'          => $this->siteDieng->id,
    ]);

    expect(LunchGuestReservation::inUnit($this->patuhaIds)->pluck('id'))
        ->toContain($guestP->id)->not->toContain($guestD->id);
});

it('GA unit hanya melihat data unitnya di dashboard GA', function () {
    $permP = Permohonan::factory()->create([
        'request_number'       => 'REQ-HTTP-P',
        'status'               => 'pending',
        'consumerial_tools_id' => ($this->makeVehicle)('B-HTTP-P', $this->sitePTH->id)->id,
        'unit_id'              => $this->unitPatuha->id,
    ]);
    $permD = Permohonan::factory()->create([
        'request_number'       => 'REQ-HTTP-D',
        'status'               => 'pending',
        'consumerial_tools_id' => ($this->makeVehicle)('B-HTTP-D', $this->siteDieng->id)->id,
        'unit_id'              => $this->unitDieng->id,
    ]);

    $this->actingAs($this->ga)
        ->get(route('admin-dashboard'))
        ->assertOk()
        ->assertSee('REQ-HTTP-P')
        ->assertDontSee('REQ-HTTP-D');

    $this->actingAs($this->globalGa)
        ->get(route('admin-dashboard'))
        ->assertOk()
        ->assertSee('REQ-HTTP-P')
        ->assertSee('REQ-HTTP-D');
});

it('GA unit hanya melihat transaksi lunch site unitnya', function () {
    LunchTransaction::create([
        'transaction_date' => now()->toDateString(),
        'site_id'          => $this->sitePTH->id,
        'raw_alias'        => 'PEG-PATUHA-XYZ',
        'employee_name'    => 'Pegawai Patuha',
    ]);
    LunchTransaction::create([
        'transaction_date' => now()->toDateString(),
        'site_id'          => $this->siteDieng->id,
        'raw_alias'        => 'PEG-DIENG-XYZ',
        'employee_name'    => 'Pegawai Dieng',
    ]);

    $this->actingAs($this->ga)
        ->get(route('lunch.transactions.index'))
        ->assertOk()
        ->assertSee('PEG-PATUHA-XYZ')
        ->assertDontSee('PEG-DIENG-XYZ');

    $this->actingAs($this->globalGa)
        ->get(route('lunch.transactions.index'))
        ->assertOk()
        ->assertSee('PEG-PATUHA-XYZ')
        ->assertSee('PEG-DIENG-XYZ');
});
