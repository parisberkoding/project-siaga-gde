<?php

use App\Models\Approved_Request;
use App\Models\LpjPenggantian;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\SuratSpdDetail;
use App\Models\SuratSpdFundAddition;
use App\Models\Transaction_Proofs;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Hardening pasca-QC (ronde P5) untuk tiga celah lintas unit:
 *
 *  - F-01 / BUG-SEC-001 : detail LPJ (GA, Manager, pengguna) bisa dibuka
 *                         lintas unit lewat URL langsung.
 *  - F-02 / BUG-BBM-004 & BUG-BBM-005 : bulk-process Kelola Permohonan,
 *                         print-report, serta update/cancel/delete
 *                         permohonan & laporan BBM tidak dibatasi unit.
 *  - F-03 / BUG-SPD-002 : fund-additions dan save-mendadak-budget menulis
 *                         dana ke SPD unit lain.
 *
 * Setiap skenario negatif dilengkapi skenario positif (pemilik/pengelola
 * unit yang benar tetap boleh) agar perbaikan tidak terlalu ketat.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);

    $this->unitPatuha = Unit::where('code', 'PATUHA')->firstOrFail();
    $this->unitDieng = Unit::where('code', 'DIENG')->firstOrFail();

    $this->buatUser = function (string $roleCode, string $legacyRole, ?int $unitId): User {
        $user = User::factory()->create([
            'role'    => $legacyRole,
            'unit_id' => $unitId,
        ]);

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

    $this->suratDieng = SuratSpd::factory()->create([
        'user_id'    => $this->pegawaiDieng->id,
        'vehicle_id' => $this->vehicleDieng->id,
        'unit_id'    => $this->unitDieng->id,
        'status'     => 'approved',
        'approved_at' => now(),
    ]);

    $this->lpjDieng = LpjPenggantian::create([
        'surat_spd_id' => $this->suratDieng->id,
        'user_id'      => $this->pegawaiDieng->id,
        'vehicle_id'   => $this->vehicleDieng->id,
        'nomor_polisi' => 'D 1234 DGN',
        'nama'         => 'Pegawai Dieng',
        'jabatan'      => 'Staff',
        'uraian'       => 'Perjalanan dinas Dieng',
        'uang_dari'    => 'KAS',
        'status'       => 'pending',
    ]);

    $this->buatPermohonan = function (Unit $unit, User $requester, VehicleAndToolsConsumers $vehicle, array $overrides = []): Permohonan {
        return Permohonan::factory()->create(array_merge([
            'requester_id'         => $requester->id,
            'consumerial_tools_id' => $vehicle->id,
            'unit_id'              => $unit->id,
            'status'               => 'pending',
            'bill_amounts'         => 300000,
            'request_date'         => now(),
        ], $overrides));
    };
});

// ═══════════════════════════════════════════════════════════════════════════
// F-01 / BUG-SEC-001 — Detail LPJ lintas unit harus 404
// ═══════════════════════════════════════════════════════════════════════════

it('F-01 menolak Admin GA unit lain membuka detail LPJ (404, bukan 403)', function () {
    $this->actingAs($this->gaPatuha)
        ->get(route('lpj.superadmin.show', $this->lpjDieng))
        ->assertNotFound();
});

it('F-01 menolak Manager unit lain membuka detail LPJ management (404)', function () {
    $this->actingAs($this->managerPatuha)
        ->get(route('lpj.management.show', $this->lpjDieng))
        ->assertNotFound();
});

it('F-01 menolak pegawai unit lain membuka halaman validasi LPJ (404)', function () {
    $this->actingAs($this->pegawaiPatuha)
        ->get(route('lpj.pengguna.show', $this->lpjDieng))
        ->assertNotFound();
});

it('F-01 tetap mengizinkan pengelola unit yang benar membuka detail LPJ', function () {
    $this->actingAs($this->gaDieng)
        ->get(route('lpj.superadmin.show', $this->lpjDieng))
        ->assertOk();

    $this->actingAs($this->managerDieng)
        ->get(route('lpj.management.show', $this->lpjDieng))
        ->assertOk();
});

it('F-01 tetap mengizinkan pemilik dan penumpang membuka halaman validasi LPJ', function () {
    // Pemilik LPJ (unit Dieng).
    $this->actingAs($this->pegawaiDieng)
        ->get(route('lpj.pengguna.show', $this->lpjDieng))
        ->assertOk();

    // Penumpang dari unit lain tetap boleh: ia memang ikut perjalanan ini.
    SuratSpdDetail::create([
        'surat_spd_id' => $this->suratDieng->id,
        'user_id'      => $this->pegawaiPatuha->id,
        'nama_lengkap' => 'Penumpang Patuha',
        'urutan'       => 1,
    ]);

    $this->actingAs($this->pegawaiPatuha)
        ->get(route('lpj.pengguna.show', $this->lpjDieng))
        ->assertOk();
});

it('F-01 menolak unduh lampiran pemberian dana LPJ unit lain (404)', function () {
    $this->actingAs($this->gaPatuha)
        ->get(route('spd.laporan.pemberian-dana', $this->lpjDieng))
        ->assertNotFound();

    // Pengelola unit yang benar tetap boleh mengunduh.
    $this->actingAs($this->gaDieng)
        ->get(route('spd.laporan.pemberian-dana', $this->lpjDieng))
        ->assertOk();
});

// ═══════════════════════════════════════════════════════════════════════════
// F-02 / BUG-BBM-004 & BUG-BBM-005 — Seluruh jalur BBM dibatasi unit
// ═══════════════════════════════════════════════════════════════════════════

it('F-02 bulk-process hanya memproses permohonan unit sendiri dan melewati unit lain', function () {
    $own = ($this->buatPermohonan)($this->unitPatuha, $this->pegawaiPatuha, $this->vehiclePatuha);
    $foreign = ($this->buatPermohonan)($this->unitDieng, $this->pegawaiDieng, $this->vehicleDieng);

    $this->actingAs($this->gaPatuha)
        ->post(route('superadmin.permohonan.bulk-process'), [
            'decisions' => json_encode([
                ['request_id' => $foreign->id, 'status' => 'approve', 'note' => 'Lintas unit'],
                ['request_id' => $own->id, 'status' => 'approve', 'note' => 'Unit sendiri'],
            ]),
        ])
        ->assertRedirect(route('superadmin.permohonan', ['tab' => 'permohonan']))
        ->assertSessionHas('success');

    expect(session('success'))->toContain('dilewati');

    // Permohonan unit lain tidak berubah dan TIDAK dibuatkan voucher.
    expect($foreign->fresh()->status)->toBe('pending')
        ->and($foreign->fresh()->authorizer_id)->toBeNull()
        ->and(Approved_Request::where('permohonan_id', $foreign->id)->exists())->toBeFalse();

    // Permohonan unit sendiri tetap diproses + voucher terbuat.
    expect($own->fresh()->status)->toBe('approved')
        ->and(Approved_Request::where('permohonan_id', $own->id)->exists())->toBeTrue();
});

it('F-02 print-report tidak memuat transaksi BBM unit lain', function () {
    $own = ($this->buatPermohonan)($this->unitPatuha, $this->pegawaiPatuha, $this->vehiclePatuha, [
        'request_number' => 'REQ-PATUHA-P5',
        'status'         => 'approved',
    ]);
    $foreign = ($this->buatPermohonan)($this->unitDieng, $this->pegawaiDieng, $this->vehicleDieng, [
        'request_number' => 'REQ-DIENG-P5',
        'status'         => 'approved',
    ]);

    $response = $this->actingAs($this->gaPatuha)
        ->get(route('superadmin.print-report', [
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date'   => now()->addDay()->toDateString(),
        ]));

    $response->assertOk();

    $pdf = $response->getContent();

    // Dompdf mengompresi content stream (FlateDecode); dekompresi dulu agar
    // teks yang tercetak benar-benar bisa diperiksa.
    $teks = '';
    if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches)) {
        foreach ($matches[1] as $stream) {
            $decoded = @gzuncompress($stream);

            if ($decoded !== false) {
                $teks .= $decoded;
            }
        }
    }

    // Nomor permohonan unit sendiri harus tercetak, unit lain tidak boleh.
    expect($teks)->toContain($own->request_number)
        ->not->toContain($foreign->request_number);
});

it('F-02 menolak update/cancel/delete permohonan milik unit lain (404)', function () {
    $foreign = ($this->buatPermohonan)($this->unitDieng, $this->pegawaiDieng, $this->vehicleDieng);

    $this->actingAs($this->gaPatuha)
        ->post(route('superadmin.permohonan.update', $foreign->id), [
            'consumerial_tools_id' => $this->vehicleDieng->id,
            'gasoline_type'        => 'Pertamax',
            'bill_amounts'         => 200000,
            'requester_notes'      => 'Uji lintas unit',
            'authorizer_notes'     => 'Uji lintas unit',
        ])
        ->assertNotFound();

    $this->actingAs($this->gaPatuha)
        ->post(route('superadmin.permohonan.cancel', $foreign->id), [
            'cancel_reason' => 'Coba lintas unit',
        ])
        ->assertNotFound();

    $this->actingAs($this->gaPatuha)
        ->post(route('superadmin.permohonan.delete', $foreign->id))
        ->assertNotFound();

    $fresh = $foreign->fresh();

    expect($fresh)->not->toBeNull()
        ->and($fresh->status)->toBe('pending')
        ->and((float) $fresh->bill_amounts)->toBe(300000.0);
});

it('F-02 menolak update/delete laporan transaksi BBM milik unit lain (404)', function () {
    $foreign = ($this->buatPermohonan)($this->unitDieng, $this->pegawaiDieng, $this->vehicleDieng);

    $proof = Transaction_Proofs::create([
        'transaction_id'   => 'TRX-DIENG-P5',
        'req_id'           => $foreign->id,
        'requester_id'     => $this->pegawaiDieng->id,
        'voucher_number'   => 'VCH-DIENG-P5',
        'purchase_datetime' => now(),
        'fuel_volume'      => 10,
        'struk_bbm_path'   => 'transaction_proofs/struk/dieng-p5.jpg',
    ]);

    $this->actingAs($this->gaPatuha)
        ->post(route('superadmin.laporan.update', $proof->id), [
            'purchase_date' => now()->toDateString(),
            'fuel_volume'   => 12,
        ])
        ->assertNotFound();

    $this->actingAs($this->gaPatuha)
        ->post(route('superadmin.laporan.delete', $proof->id))
        ->assertNotFound();

    expect(Transaction_Proofs::whereKey($proof->id)->exists())->toBeTrue()
        ->and((float) $proof->fresh()->fuel_volume)->toBe(10.0);
});

it('F-02 tetap mengizinkan pengelola unit sendiri update/cancel/delete permohonan dan laporan', function () {
    $own = ($this->buatPermohonan)($this->unitPatuha, $this->pegawaiPatuha, $this->vehiclePatuha);

    $this->actingAs($this->gaPatuha)
        ->post(route('superadmin.permohonan.update', $own->id), [
            'consumerial_tools_id' => $this->vehiclePatuha->id,
            'gasoline_type'        => 'Pertalite',
            'bill_amounts'         => 250000,
            'requester_notes'      => 'Uji unit sendiri',
            'authorizer_notes'     => 'Uji unit sendiri',
        ])
        ->assertRedirect(route('superadmin.permohonan', ['tab' => 'permohonan']));

    expect((float) $own->fresh()->bill_amounts)->toBe(250000.0);

    $proof = Transaction_Proofs::create([
        'transaction_id'   => 'TRX-PATUHA-P5',
        'req_id'           => $own->id,
        'requester_id'     => $this->pegawaiPatuha->id,
        'voucher_number'   => 'VCH-PATUHA-P5',
        'purchase_datetime' => now(),
        'fuel_volume'      => 8,
        'struk_bbm_path'   => 'transaction_proofs/struk/patuha-p5.jpg',
    ]);

    $this->actingAs($this->gaPatuha)
        ->post(route('superadmin.laporan.update', $proof->id), [
            'purchase_date' => now()->toDateString(),
            'fuel_volume'   => 9,
        ])
        ->assertRedirect(route('superadmin.permohonan', ['tab' => 'laporan']));

    expect((float) $proof->fresh()->fuel_volume)->toBe(9.0);

    $this->actingAs($this->gaPatuha)
        ->post(route('superadmin.laporan.delete', $proof->id))
        ->assertRedirect(route('superadmin.permohonan', ['tab' => 'laporan']));

    expect(Transaction_Proofs::whereKey($proof->id)->exists())->toBeFalse();

    $this->actingAs($this->gaPatuha)
        ->post(route('superadmin.permohonan.cancel', $own->id), [
            'cancel_reason' => 'Dibatalkan unit sendiri',
        ])
        ->assertRedirect(route('superadmin.permohonan', ['tab' => 'permohonan']));

    expect($own->fresh()->status)->toBe('cancelled_by_driver');
});

// ═══════════════════════════════════════════════════════════════════════════
// F-03 / BUG-SPD-002 — Aksi tulis keuangan SPD dibatasi unit
// ═══════════════════════════════════════════════════════════════════════════

it('F-03 menolak fund-addition ke SPD unit lain (403) dan tidak menulis baris dana', function () {
    $this->actingAs($this->gaPatuha)
        ->post(route('surat.superadmin.fund-additions.store', $this->suratDieng), [
            'nominal_tambahan' => 750000,
            'alasan'           => 'Coba menambah dana lintas unit',
        ])
        ->assertForbidden();

    expect(SuratSpdFundAddition::where('surat_spd_id', $this->suratDieng->id)->exists())->toBeFalse();
});

it('F-03 menolak save-mendadak-budget ke SPD unit lain (403) dan tidak menulis budget', function () {
    $suratMendadakDieng = SuratSpd::factory()->create([
        'user_id'    => $this->pegawaiDieng->id,
        'vehicle_id' => $this->vehicleDieng->id,
        'unit_id'    => $this->unitDieng->id,
        'status'     => 'approved',
        'type'       => 'mendadak',
    ]);

    $this->actingAs($this->gaPatuha)
        ->post(route('surat.superadmin.save-mendadak-budget', $suratMendadakDieng), [
            'budgets' => [
                ['tanggal' => now()->toDateString(), 'nominal' => 500000],
            ],
        ])
        ->assertForbidden();

    expect($suratMendadakDieng->fresh()->budgets()->count())->toBe(0);
});

it('F-03 tetap mengizinkan pengelola unit sendiri menulis dana SPD', function () {
    $this->actingAs($this->gaDieng)
        ->post(route('surat.superadmin.fund-additions.store', $this->suratDieng), [
            'nominal_tambahan' => 250000,
            'alasan'           => 'Tambahan dana resmi unit Dieng',
        ])
        ->assertRedirect();

    expect(SuratSpdFundAddition::where('surat_spd_id', $this->suratDieng->id)->count())->toBe(1);

    $suratMendadakDieng = SuratSpd::factory()->create([
        'user_id'    => $this->pegawaiDieng->id,
        'vehicle_id' => $this->vehicleDieng->id,
        'unit_id'    => $this->unitDieng->id,
        'status'     => 'approved',
        'type'       => 'mendadak',
    ]);

    $this->actingAs($this->gaDieng)
        ->post(route('surat.superadmin.save-mendadak-budget', $suratMendadakDieng), [
            'budgets' => [
                ['tanggal' => now()->toDateString(), 'nominal' => 400000],
            ],
        ])
        ->assertRedirect();

    expect($suratMendadakDieng->fresh()->budgets()->count())->toBe(1);
});
