<?php

use App\Models\LpjPenggantian;
use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\SuratSpdDetail;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * BUG-SPD-002 (kebocoran data antar unit) & BUG-EXP-002/BUG-SEC-001
 * (cetak surat/LPJ hanya untuk pemilik, penumpang, unit sendiri, atau global).
 */
beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->unitPatuha = Unit::create(['code' => 'PATUHA', 'name' => 'Unit Patuha']);
    $this->unitDieng = Unit::create(['code' => 'DIENG', 'name' => 'Unit Dieng']);

    $this->buatUser = function (string $roleCode, string $legacyRole, ?int $unitId): User {
        $user = User::factory()->create([
            'role' => $legacyRole,
            'unit_id' => $unitId,
        ]);

        $role = Role::where('code', $roleCode)->first();

        if ($role) {
            $user->roles()->attach($role->id, ['is_primary' => true]);
        }

        return $user->fresh();
    };

    $this->gaPatuha = ($this->buatUser)('general_affairs', 'general_affairs', $this->unitPatuha->id);
    $this->gaDieng = ($this->buatUser)('general_affairs', 'general_affairs', $this->unitDieng->id);
    $this->gaGlobal = ($this->buatUser)('general_affairs', 'general_affairs', null);
    $this->superGlobal = ($this->buatUser)('superadmin', 'superadmin', null);

    $this->driverPatuha = ($this->buatUser)('requester', 'requester', $this->unitPatuha->id);
    $this->driverDieng = ($this->buatUser)('requester', 'requester', $this->unitDieng->id);

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved' => true,
        'is_active' => true,
    ]);

    $this->suratDieng = SuratSpd::factory()->create([
        'user_id' => $this->driverDieng->id,
        'vehicle_id' => $this->vehicle->id,
        'unit_id' => $this->unitDieng->id,
        'status' => 'pending',
        'pemberi_tugas_nama' => 'Manager Dieng',
    ]);
});

it('menolak Admin GA unit lain membuka detail surat unit lain', function () {
    $this->actingAs($this->gaPatuha)
        ->get(route('surat.superadmin.show', $this->suratDieng))
        ->assertStatus(403);
});

it('menolak Admin GA unit lain approve/reject dan status tidak berubah', function () {
    $this->actingAs($this->gaPatuha)
        ->post(route('surat.superadmin.approve', $this->suratDieng))
        ->assertStatus(403);

    expect($this->suratDieng->fresh()->status)->toBe('pending');

    $this->actingAs($this->gaPatuha)
        ->post(route('surat.superadmin.reject', $this->suratDieng), [
            'rejection_reason' => 'Alasan penolakan yang cukup panjang',
        ])
        ->assertStatus(403);

    $surat = $this->suratDieng->fresh();

    expect($surat->status)->toBe('pending')
        ->and($surat->rejected_by)->toBeNull()
        ->and($surat->rejection_reason)->toBeNull();
});

it('mengizinkan user global membuka detail surat semua unit', function () {
    $this->actingAs($this->superGlobal)
        ->get(route('surat.superadmin.show', $this->suratDieng))
        ->assertStatus(200);
});

it('surat tanpa unit hanya boleh dibuka user global', function () {
    $suratTanpaUnit = SuratSpd::factory()->create([
        'user_id' => $this->driverDieng->id,
        'vehicle_id' => $this->vehicle->id,
        'unit_id' => null,
        'status' => 'pending',
        'pemberi_tugas_nama' => 'Manager Tanpa Unit',
    ]);

    $this->actingAs($this->gaPatuha)
        ->get(route('surat.superadmin.show', $suratTanpaUnit))
        ->assertStatus(403);

    $this->actingAs($this->gaGlobal)
        ->get(route('surat.superadmin.show', $suratTanpaUnit))
        ->assertStatus(200);
});

it('SPD completed tetap bisa dicetak pemiliknya, tetapi 404 untuk user unit lain', function () {
    $suratSelesai = SuratSpd::factory()->create([
        'user_id' => $this->driverPatuha->id,
        'vehicle_id' => $this->vehicle->id,
        'unit_id' => $this->unitPatuha->id,
        'status' => 'completed',
        'approved_at' => now(),
    ]);

    $this->actingAs($this->driverPatuha)
        ->get(route('spd.surat.print', $suratSelesai))
        ->assertStatus(200);

    // GA unit yang sama boleh mencetak.
    $this->actingAs($this->gaPatuha)
        ->get(route('spd.surat.print', $suratSelesai))
        ->assertStatus(200);

    // Unit lain tidak boleh (record ada -> 404, bukan 403).
    $this->actingAs($this->driverDieng)
        ->get(route('spd.surat.print', $suratSelesai))
        ->assertStatus(404);

    $this->actingAs($this->gaDieng)
        ->get(route('spd.surat.print', $suratSelesai))
        ->assertStatus(404);
});

it('penumpang boleh mencetak SPD walau berbeda unit', function () {
    $suratPatuha = SuratSpd::factory()->create([
        'user_id' => $this->driverPatuha->id,
        'vehicle_id' => $this->vehicle->id,
        'unit_id' => $this->unitPatuha->id,
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    SuratSpdDetail::create([
        'surat_spd_id' => $suratPatuha->id,
        'user_id' => $this->driverDieng->id,
        'nama_lengkap' => 'Penumpang Dieng',
        'urutan' => 1,
    ]);

    $this->actingAs($this->driverDieng)
        ->get(route('spd.surat.print', $suratPatuha))
        ->assertStatus(200);
});

it('export LPJ memakai aturan visibilitas yang sama (404 untuk unit lain)', function () {
    $suratPatuha = SuratSpd::factory()->create([
        'user_id' => $this->driverPatuha->id,
        'vehicle_id' => $this->vehicle->id,
        'unit_id' => $this->unitPatuha->id,
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    $lpj = LpjPenggantian::create([
        'surat_spd_id' => $suratPatuha->id,
        'user_id' => $this->driverPatuha->id,
        'nama' => 'Driver Patuha',
        'jabatan' => 'Pengemudi',
        'vehicle_id' => $this->vehicle->id,
        'nomor_polisi' => 'B 1234 TEST',
        'uraian' => 'Perjalanan dinas uji',
        'uang_dari' => 'KAS',
        'status' => 'approved',
    ]);

    $this->actingAs($this->driverDieng)
        ->get(route('spd.laporan.export', $lpj))
        ->assertStatus(404);

    $this->actingAs($this->driverDieng)
        ->get(route('spd.laporan.print', $lpj))
        ->assertStatus(404);

    $this->actingAs($this->driverPatuha)
        ->get(route('spd.laporan.export', $lpj))
        ->assertStatus(200);
});

/**
 * BUG-SEC-001: "satu unit" saja tidak cukup. Requester lain di unit yang
 * SAMA tetap tidak boleh membuka cetak SPD / cetak & export LPJ milik
 * requester lain. Yang boleh unit-wide hanya peran pengelola (Admin GA,
 * Manager, GA pencairan).
 */
it('requester lain di unit yang sama tidak boleh mencetak SPD orang lain', function () {
    $suratPatuha = SuratSpd::factory()->create([
        'user_id' => $this->driverPatuha->id,
        'vehicle_id' => $this->vehicle->id,
        'unit_id' => $this->unitPatuha->id,
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    $driverLain = ($this->buatUser)('requester', 'requester', $this->unitPatuha->id);

    $this->actingAs($driverLain)
        ->get(route('spd.surat.print', $suratPatuha))
        ->assertStatus(404);
});

it('requester lain di unit yang sama tidak boleh mencetak atau export LPJ orang lain', function () {
    $suratPatuha = SuratSpd::factory()->create([
        'user_id' => $this->driverPatuha->id,
        'vehicle_id' => $this->vehicle->id,
        'unit_id' => $this->unitPatuha->id,
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    $lpj = LpjPenggantian::create([
        'surat_spd_id' => $suratPatuha->id,
        'user_id' => $this->driverPatuha->id,
        'nama' => 'Driver Patuha',
        'jabatan' => 'Pengemudi',
        'vehicle_id' => $this->vehicle->id,
        'nomor_polisi' => 'B 1234 TEST',
        'uraian' => 'Perjalanan dinas uji',
        'uang_dari' => 'KAS',
        'status' => 'approved',
    ]);

    $driverLain = ($this->buatUser)('requester', 'requester', $this->unitPatuha->id);

    $this->actingAs($driverLain)
        ->get(route('spd.laporan.export', $lpj))
        ->assertStatus(404);

    $this->actingAs($driverLain)
        ->get(route('spd.laporan.print', $lpj))
        ->assertStatus(404);
});
