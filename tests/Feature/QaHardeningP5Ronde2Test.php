<?php

use App\Models\LpjPenggantian;
use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\SuratSpdBudget;
use App\Models\SuratSpdDetail;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Ronde 2 hardening P5 — sisa celah tulis lintas unit (satu keluarga
 * BUG-SEC-001/BUG-SPD-002):
 *
 *  - R2-01: aksi tulis LPJ (approve, approve-ga, reject, management.approve,
 *           pengguna.approve, keuangan) belum dibatasi unit/keberhakan.
 *  - R2-02: SpdPencairanController (index/store/update/destroy) belum
 *           dibatasi unit, padahal menulis dana SPD.
 *  - R2-03: dropdown approver pada form cetak laporan BBM memuat nama
 *           approver seluruh unit.
 *
 * Semantik: baca/detail -> 404, tulis -> 403, user global tetap boleh.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);

    $this->unitPatuha = Unit::where('code', 'PATUHA')->firstOrFail();
    $this->unitDieng = Unit::where('code', 'DIENG')->firstOrFail();

    $this->buatUser = function (string $roleCode, string $legacyRole, ?int $unitId, array $overrides = []): User {
        $user = User::factory()->create(array_merge([
            'role'    => $legacyRole,
            'unit_id' => $unitId,
        ], $overrides));

        $role = Role::where('code', $roleCode)->first();

        if ($role) {
            $user->roles()->attach($role->id, ['is_primary' => true]);
        }

        return $user->fresh();
    };

    $this->gaPatuha = ($this->buatUser)('general_affairs', 'admin', $this->unitPatuha->id);
    $this->gaDieng = ($this->buatUser)('general_affairs', 'admin', $this->unitDieng->id);
    $this->managerPatuha = ($this->buatUser)('manager', 'manager', $this->unitPatuha->id);
    $this->managerDieng = ($this->buatUser)('manager', 'manager', $this->unitDieng->id);
    $this->pegawaiPatuha = ($this->buatUser)('pengguna_spd', 'requester', $this->unitPatuha->id);
    $this->pegawaiDieng = ($this->buatUser)('pengguna_spd', 'requester', $this->unitDieng->id);
    $this->superGlobal = ($this->buatUser)('superadmin', 'superadmin', null);

    $this->vehiclePatuha = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
        'unit_id'          => $this->unitPatuha->id,
    ]);
    $this->vehicleDieng = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
        'unit_id'          => $this->unitDieng->id,
    ]);

    $this->buatLpj = function (User $pemilik, Unit $unit, VehicleAndToolsConsumers $vehicle, array $overrides = []): LpjPenggantian {
        $surat = SuratSpd::factory()->create([
            'user_id'     => $pemilik->id,
            'vehicle_id'  => $vehicle->id,
            'unit_id'     => $unit->id,
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        return LpjPenggantian::create(array_merge([
            'surat_spd_id' => $surat->id,
            'user_id'      => $pemilik->id,
            'vehicle_id'   => $vehicle->id,
            'nomor_polisi' => 'D 1234 R2',
            'nama'         => $pemilik->nama_lengkap ?? 'Pegawai R2',
            'jabatan'      => 'Staff',
            'uraian'       => 'Perjalanan dinas ronde 2',
            'uang_dari'    => 'KAS',
            'status'       => 'pending',
        ], $overrides));
    };

    $this->lpjDieng = ($this->buatLpj)($this->pegawaiDieng, $this->unitDieng, $this->vehicleDieng);
});

// ═══════════════════════════════════════════════════════════════════════════
// R2-01 — Aksi tulis LPJ lintas unit ditolak 403
// ═══════════════════════════════════════════════════════════════════════════

it('R2 menolak Manager unit lain approve-final LPJ (403)', function () {
    $lpj = ($this->buatLpj)($this->pegawaiDieng, $this->unitDieng, $this->vehicleDieng, [
        'approved_by_pengguna'  => $this->pegawaiDieng->id,
        'approved_pengguna_at'  => now(),
        'approved_by_divisi_ga' => $this->gaDieng->id,
        'approved_divisi_ga_at' => now(),
    ]);

    $this->actingAs($this->managerPatuha)
        ->post(route('lpj.superadmin.approve', $lpj))
        ->assertForbidden();

    $fresh = $lpj->fresh();

    expect($fresh->status)->toBe('pending')
        ->and($fresh->approved_by_management)->toBeNull();
});

it('R2 menolak GA unit lain approve-ga LPJ (403)', function () {
    $this->actingAs($this->gaPatuha)
        ->post(route('lpj.superadmin.approve-ga', $this->lpjDieng))
        ->assertForbidden();

    $fresh = $this->lpjDieng->fresh();

    expect($fresh->approved_by_divisi_ga)->toBeNull()
        ->and($fresh->status)->toBe('pending');
});

it('R2 menolak GA unit lain reject LPJ (403)', function () {
    $this->actingAs($this->gaPatuha)
        ->post(route('lpj.superadmin.reject', $this->lpjDieng), [
            'rejection_reason' => 'Alasan penolakan lintas unit',
        ])
        ->assertForbidden();

    $fresh = $this->lpjDieng->fresh();

    expect($fresh->status)->toBe('pending')
        ->and($fresh->rejected_by)->toBeNull()
        ->and($fresh->rejection_reason)->toBeNull();
});

it('R2 menolak Manager unit lain management.approve LPJ (403)', function () {
    $lpj = ($this->buatLpj)($this->pegawaiDieng, $this->unitDieng, $this->vehicleDieng, [
        'approved_by_pengguna'  => $this->pegawaiDieng->id,
        'approved_pengguna_at'  => now(),
        'approved_by_divisi_ga' => $this->gaDieng->id,
        'approved_divisi_ga_at' => now(),
    ]);

    $this->actingAs($this->managerPatuha)
        ->post(route('lpj.management.approve', $lpj))
        ->assertForbidden();

    $fresh = $lpj->fresh();

    expect($fresh->approved_by_management)->toBeNull()
        ->and($fresh->status)->toBe('pending');
});

it('R2 menolak pegawai unit lain memvalidasi LPJ (403)', function () {
    $this->actingAs($this->pegawaiPatuha)
        ->post(route('lpj.pengguna.approve', $this->lpjDieng), ['pernyataan' => '1'])
        ->assertForbidden();

    expect($this->lpjDieng->fresh()->approved_by_pengguna)->toBeNull();
});

it('R2 menolak GA unit lain mengubah data keuangan LPJ (403)', function () {
    $this->actingAs($this->gaPatuha)
        ->put(route('lpj.superadmin.keuangan', $this->lpjDieng), [
            'uang_dari'        => 'BANK LINTAS UNIT',
            'jumlah_diberikan' => 999000,
        ])
        ->assertForbidden();

    $fresh = $this->lpjDieng->fresh();

    expect($fresh->uang_dari)->toBe('KAS')
        ->and((float) $fresh->jumlah_diberikan)->toBe(0.0);
});

it('R2 tetap mengizinkan pengelola unit sendiri melakukan aksi tulis LPJ', function () {
    // approve-ga oleh GA unit sendiri.
    $this->actingAs($this->gaDieng)
        ->post(route('lpj.superadmin.approve-ga', $this->lpjDieng))
        ->assertRedirect();
    expect($this->lpjDieng->fresh()->approved_by_divisi_ga)->toBe($this->gaDieng->id);

    // management.approve oleh Manager unit sendiri (tahap user sudah selesai).
    $this->lpjDieng->update([
        'approved_by_pengguna' => $this->pegawaiDieng->id,
        'approved_pengguna_at' => now(),
    ]);
    $this->actingAs($this->managerDieng)
        ->post(route('lpj.management.approve', $this->lpjDieng))
        ->assertRedirect();
    expect($this->lpjDieng->fresh()->approved_by_management)->toBe($this->managerDieng->id);

    // keuangan oleh GA unit sendiri.
    $this->actingAs($this->gaDieng)
        ->put(route('lpj.superadmin.keuangan', $this->lpjDieng), [
            'uang_dari'        => 'KAS UNIT DIENG',
            'jumlah_diberikan' => 125000,
        ])
        ->assertRedirect();
    expect($this->lpjDieng->fresh()->uang_dari)->toBe('KAS UNIT DIENG');

    // reject oleh GA unit sendiri (LPJ baru).
    $lpjReject = ($this->buatLpj)($this->pegawaiDieng, $this->unitDieng, $this->vehicleDieng);
    $this->actingAs($this->gaDieng)
        ->post(route('lpj.superadmin.reject', $lpjReject), [
            'rejection_reason' => 'Ditolak sah oleh unit sendiri',
        ])
        ->assertRedirect();
    expect($lpjReject->fresh()->status)->toBe('rejected');

    // validasi pengguna oleh pemilik LPJ.
    $lpjPengguna = ($this->buatLpj)($this->pegawaiDieng, $this->unitDieng, $this->vehicleDieng);
    $this->actingAs($this->pegawaiDieng)
        ->post(route('lpj.pengguna.approve', $lpjPengguna), ['pernyataan' => '1'])
        ->assertRedirect();
    expect($lpjPengguna->fresh()->approved_by_pengguna)->toBe($this->pegawaiDieng->id);

    // approve-final oleh Manager unit sendiri.
    $lpjFinal = ($this->buatLpj)($this->pegawaiDieng, $this->unitDieng, $this->vehicleDieng, [
        'approved_by_pengguna'  => $this->pegawaiDieng->id,
        'approved_pengguna_at'  => now(),
        'approved_by_divisi_ga' => $this->gaDieng->id,
        'approved_divisi_ga_at' => now(),
    ]);
    $this->actingAs($this->managerDieng)
        ->post(route('lpj.superadmin.approve', $lpjFinal))
        ->assertRedirect();
    expect($lpjFinal->fresh()->status)->toBe('approved');
});

it('R2 tetap mengizinkan penumpang lintas unit memvalidasi LPJ yang ia ikuti', function () {
    SuratSpdDetail::create([
        'surat_spd_id' => $this->lpjDieng->surat_spd_id,
        'user_id'      => $this->pegawaiPatuha->id,
        'nama_lengkap' => 'Penumpang Patuha R2',
        'urutan'       => 1,
    ]);

    $this->actingAs($this->pegawaiPatuha)
        ->post(route('lpj.pengguna.approve', $this->lpjDieng), ['pernyataan' => '1'])
        ->assertRedirect();

    expect($this->lpjDieng->fresh()->approved_by_pengguna)->toBe($this->pegawaiPatuha->id);
});

it('R2 tetap mengizinkan user global menulis LPJ lintas unit', function () {
    $this->actingAs($this->superGlobal)
        ->post(route('lpj.superadmin.approve-ga', $this->lpjDieng))
        ->assertRedirect();

    expect($this->lpjDieng->fresh()->approved_by_divisi_ga)->toBe($this->superGlobal->id);
});

// ═══════════════════════════════════════════════════════════════════════════
// R2-02 — SpdPencairanController: baca 404, tulis 403
// ═══════════════════════════════════════════════════════════════════════════

it('R2 menolak GA unit lain membuka halaman pencairan LPJ (404)', function () {
    $this->actingAs($this->gaPatuha)
        ->get(route('lpj.superadmin.pencairan.index', $this->lpjDieng))
        ->assertNotFound();
});

it('R2 menolak GA unit lain menambah/mengubah/menghapus pencairan LPJ (403)', function () {
    $pencairan = SuratSpdBudget::create([
        'surat_spd_id'     => $this->lpjDieng->surat_spd_id,
        'tanggal'          => now()->toDateString(),
        'nominal'          => 300000,
        'bentuk_pencairan' => 'Tunai',
        'urutan'           => 1,
    ]);

    $this->actingAs($this->gaPatuha)
        ->post(route('lpj.superadmin.pencairan.store', $this->lpjDieng), [
            'tanggal'          => now()->toDateString(),
            'nominal'          => 450000,
            'bentuk_pencairan' => 'Tunai',
        ])
        ->assertForbidden();

    $this->actingAs($this->gaPatuha)
        ->put(route('lpj.superadmin.pencairan.update', [$this->lpjDieng, $pencairan]), [
            'tanggal'          => now()->toDateString(),
            'nominal'          => 999000,
            'bentuk_pencairan' => 'Tunai',
        ])
        ->assertForbidden();

    $this->actingAs($this->gaPatuha)
        ->delete(route('lpj.superadmin.pencairan.destroy', [$this->lpjDieng, $pencairan]))
        ->assertForbidden();

    expect(SuratSpdBudget::where('surat_spd_id', $this->lpjDieng->surat_spd_id)->count())->toBe(1)
        ->and((float) $pencairan->fresh()->nominal)->toBe(300000.0);
});

it('R2 tetap mengizinkan GA unit sendiri mengelola pencairan LPJ', function () {
    $this->actingAs($this->gaDieng)
        ->get(route('lpj.superadmin.pencairan.index', $this->lpjDieng))
        ->assertOk();

    $this->actingAs($this->gaDieng)
        ->post(route('lpj.superadmin.pencairan.store', $this->lpjDieng), [
            'tanggal'          => now()->toDateString(),
            'nominal'          => 500000,
            'bentuk_pencairan' => 'Tunai',
        ])
        ->assertRedirect();

    $pencairan = SuratSpdBudget::where('surat_spd_id', $this->lpjDieng->surat_spd_id)->firstOrFail();

    $this->actingAs($this->gaDieng)
        ->put(route('lpj.superadmin.pencairan.update', [$this->lpjDieng, $pencairan]), [
            'tanggal'          => now()->toDateString(),
            'nominal'          => 600000,
            'bentuk_pencairan' => 'Tunai',
        ])
        ->assertRedirect();

    expect((float) $pencairan->fresh()->nominal)->toBe(600000.0);

    $this->actingAs($this->gaDieng)
        ->delete(route('lpj.superadmin.pencairan.destroy', [$this->lpjDieng, $pencairan]))
        ->assertRedirect();

    expect(SuratSpdBudget::whereKey($pencairan->id)->exists())->toBeFalse();
});

it('R2 tetap mengizinkan user global membuka pencairan lintas unit', function () {
    $this->actingAs($this->superGlobal)
        ->get(route('lpj.superadmin.pencairan.index', $this->lpjDieng))
        ->assertOk();
});

// ═══════════════════════════════════════════════════════════════════════════
// R2-03 — Approver form cetak BBM hanya unit sendiri
// ═══════════════════════════════════════════════════════════════════════════

it('R2 form cetak BBM hanya menampilkan approver unit sendiri', function () {
    ($this->buatUser)('general_affairs', 'admin', $this->unitPatuha->id, [
        'nama_lengkap' => 'Approver Patuha R2',
    ]);
    ($this->buatUser)('general_affairs', 'admin', $this->unitDieng->id, [
        'nama_lengkap' => 'Approver Dieng R2',
    ]);

    $this->actingAs($this->gaPatuha)
        ->get(route('superadmin.print-report.form'))
        ->assertOk()
        ->assertSee('Approver Patuha R2', false)
        ->assertDontSee('Approver Dieng R2', false);

    // Endpoint yang sama tanpa parameter tanggal juga tidak boleh bocor.
    $this->actingAs($this->gaPatuha)
        ->get(route('superadmin.print-report'))
        ->assertOk()
        ->assertSee('Approver Patuha R2', false)
        ->assertDontSee('Approver Dieng R2', false);

    // User global tetap melihat semua approver (tidak over-blocking).
    $this->actingAs($this->superGlobal)
        ->get(route('superadmin.print-report.form'))
        ->assertOk()
        ->assertSee('Approver Patuha R2', false)
        ->assertSee('Approver Dieng R2', false);
});
