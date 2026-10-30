<?php

use App\Models\LpjPenggantian;
use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * BUG-RBAC-003 — export LPJ memakai permission `lpj.export` secara konsisten.
 * Manager (punya lpj.export, tanpa spd.view) harus bisa export; peran
 * read-only tanpa lpj.export tidak boleh lagi mengunduh dokumen LPJ.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);

    $this->unitPatuha = Unit::where('code', 'PATUHA')->firstOrFail();
    $this->unitDieng = Unit::where('code', 'DIENG')->firstOrFail();

    $this->buatUser = function (string $roleCode, ?Unit $unit): User {
        $user = User::factory()->create([
            'role' => $roleCode,
            'unit_id' => $unit?->id,
        ]);

        $user->roles()->attach(
            Role::where('code', $roleCode)->firstOrFail()->id,
            ['is_primary' => true]
        );

        return $user->fresh();
    };

    $this->requester = ($this->buatUser)('requester', $this->unitPatuha);

    $this->surat = SuratSpd::factory()->create([
        'user_id' => $this->requester->id,
        'unit_id' => $this->unitPatuha->id,
        'status' => 'approved',
    ]);

    $this->lpj = LpjPenggantian::create([
        'surat_spd_id' => $this->surat->id,
        'user_id' => $this->requester->id,
        'vehicle_id' => $this->surat->vehicle_id,
        'nomor_polisi' => 'B 1234 QA',
        'nama' => 'Requester Uji',
        'jabatan' => 'Staf',
        'uraian' => 'Uji export LPJ',
        'status' => 'approved',
    ]);
});

it('mengizinkan Manager mengekspor LPJ unitnya walau tanpa spd.view', function () {
    $manager = ($this->buatUser)('manager', $this->unitPatuha);

    expect($manager->hasPermission('lpj.export'))->toBeTrue()
        ->and($manager->hasPermission('spd.view'))->toBeFalse();

    $this->actingAs($manager)
        ->get(route('spd.laporan.export', $this->lpj))
        ->assertOk();
});

it('mengizinkan Admin GA mengekspor LPJ setelah permission lpj.export disinkronkan', function () {
    $ga = ($this->buatUser)('general_affairs', $this->unitPatuha);

    expect($ga->hasPermission('lpj.export'))->toBeTrue();

    $this->actingAs($ga)
        ->get(route('spd.laporan.export', $this->lpj))
        ->assertOk();
});

it('menolak peran tanpa lpj.export (view_only) mengekspor LPJ', function () {
    $viewOnly = ($this->buatUser)('view_only', $this->unitPatuha);

    expect($viewOnly->hasPermission('lpj.export'))->toBeFalse();

    // 404 (bukan 403) agar keberadaan dokumen tidak bocor.
    $this->actingAs($viewOnly)
        ->get(route('spd.laporan.export', $this->lpj))
        ->assertNotFound();
});

it('pemilik LPJ tanpa lpj.export tetap bisa mencetak dan mengekspor dokumennya', function () {
    expect($this->requester->hasPermission('lpj.export'))->toBeFalse();

    $this->actingAs($this->requester)
        ->get(route('spd.laporan.export', $this->lpj))
        ->assertOk();

    $this->actingAs($this->requester)
        ->get(route('spd.laporan.print', $this->lpj))
        ->assertOk();
});

it('Manager unit lain tidak bisa mengekspor LPJ lintas unit', function () {
    $managerDieng = ($this->buatUser)('manager', $this->unitDieng);

    $this->actingAs($managerDieng)
        ->get(route('spd.laporan.export', $this->lpj))
        ->assertNotFound();
});
