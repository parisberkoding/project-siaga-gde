<?php

use App\Models\SuratSpd;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * BUG-SPD-003 — nomor surat duplikat setelah SPD ke-10 dalam setahun.
 */
beforeEach(function () {
    $this->unitPatuha = Unit::create(['code' => 'PTH', 'name' => 'Unit Patuha']);
    $this->unitDieng = Unit::create(['code' => 'DNG', 'name' => 'Unit Dieng']);

    $this->user = User::factory()->create([
        'role' => 'requester',
        'unit_id' => $this->unitPatuha->id,
    ]);

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved' => true,
        'is_active' => true,
    ]);

    $this->buatSurat = function (array $overrides = []): SuratSpd {
        return SuratSpd::createWithNumberedSurat(array_merge([
            'user_id' => $this->user->id,
            'pengemudi_nama' => 'Pengemudi Uji',
            'tempat_tujuan' => 'Bandung',
            'melayani_bidang' => 'Operasional',
            'jumlah_pemakai' => 1,
            'vehicle_id' => $this->vehicle->id,
            'tanggal_berangkat' => now()->toDateString(),
            'tanggal_kembali' => now()->addDays(2)->toDateString(),
            'budget_per_hari' => 0,
            'unit_id' => $this->unitPatuha->id,
            'status' => 'pending',
        ], $overrides));
    };
});

it('memberi nomor urut 11 dan 12 secara numerik setelah sepuluh surat', function () {
    $surat = [];

    for ($i = 1; $i <= 10; $i++) {
        $surat[$i] = ($this->buatSurat)();
    }

    expect($surat[10]->nomor_urut)->toBe(10)
        ->and($surat[10]->nomor_surat)->toStartWith('010.TGS/');

    $kesebelas = ($this->buatSurat)();

    expect($kesebelas->nomor_urut)->toBe(11)
        ->and($kesebelas->nomor_surat)->toStartWith('011.TGS/');

    $keduabelas = ($this->buatSurat)();

    expect($keduabelas->nomor_urut)->toBe(12)
        ->and($keduabelas->nomor_surat)->toStartWith('012.TGS/');

    $nomorSurat = SuratSpd::pluck('nomor_surat');

    expect($nomorSurat)->toHaveCount(12)
        ->and($nomorSurat->unique()->count())->toBe(12)
        ->and(SuratSpd::orderBy('nomor_urut', 'desc')->first()->nomor_urut)->toBe(12);
});

it('melanjutkan nomor urut lintas unit dalam tahun yang sama', function () {
    ($this->buatSurat)();
    ($this->buatSurat)();

    $dariUnitLain = ($this->buatSurat)([
        'unit_id' => $this->unitDieng->id,
    ]);

    expect($dariUnitLain->nomor_urut)->toBe(3)
        ->and($dariUnitLain->nomor_surat)->toStartWith('003.TGS/DNG1.30-GDE/');
});
