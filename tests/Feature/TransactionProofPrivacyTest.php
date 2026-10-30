<?php

use App\Models\Permohonan;
use App\Models\Role;
use App\Models\StrukItem;
use App\Models\SuratSpd;
use App\Models\Transaction_Proofs;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * BUG-SEC-003 — proteksi folder media struk.
 *
 * File struk & odometer wajib berada di disk privat, hanya bisa diakses lewat
 * `/files/storage/{path}` oleh pemilik/penumpang/pengelola unit, dan file lama
 * di disk publik tetap terbaca sebagai fallback.
 */
beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');

    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);

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

    $this->buatPermohonan = function (User $owner, Unit $unit): Permohonan {
        $vehicle = VehicleAndToolsConsumers::factory()->create([
            'consumerial_type' => 'KENDARAAN',
            'is_approved' => true,
            'is_active' => true,
        ]);

        return Permohonan::factory()->create([
            'requester_id' => $owner->id,
            'consumerial_tools_id' => $vehicle->id,
            'unit_id' => $unit->id,
            'status' => 'approved',
            'bill_amounts' => 300000,
        ]);
    };

    $this->buatBukti = function (User $owner, Unit $unit): Transaction_Proofs {
        $permohonan = ($this->buatPermohonan)($owner, $unit);

        $struk = 'transaction_proofs/struk/S.TEST-'.uniqid().'.jpg';
        $odometer = 'transaction_proofs/odometer/S.TEST-ODO-'.uniqid().'.jpg';

        Storage::disk('local')->put($struk, 'struk-bytes');
        Storage::disk('local')->put($odometer, 'odometer-bytes');

        return Transaction_Proofs::create([
            'transaction_id' => 'TRX-TEST-'.uniqid(),
            'req_id' => $permohonan->id,
            'requester_id' => $owner->id,
            'unit_id' => $unit->id,
            'purchase_datetime' => now(),
            'fuel_volume' => 10,
            'struk_bbm_path' => $struk,
            'odometer_photo_path' => $odometer,
        ]);
    };
});

it('menolak akses file privat tanpa login', function () {
    $this->get('/files/storage/transaction_proofs/struk/rahasia.jpg')
        ->assertStatus(302)
        ->assertRedirect(route('login'));
});

it('mengizinkan pemilik membuka struk dan odometer miliknya', function () {
    $owner = ($this->buatUser)('requester', $this->unitPatuha);
    $proof = ($this->buatBukti)($owner, $this->unitPatuha);

    $response = $this->actingAs($owner)
        ->get('/files/storage/'.$proof->struk_bbm_path)
        ->assertStatus(200);

    expect($response->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('private');

    $this->actingAs($owner)
        ->get('/files/storage/'.$proof->odometer_photo_path)
        ->assertStatus(200);
});

it('menolak requester lain di unit yang sama membuka struk orang lain', function () {
    $owner = ($this->buatUser)('requester', $this->unitPatuha);
    $lain = ($this->buatUser)('requester', $this->unitPatuha);
    $proof = ($this->buatBukti)($owner, $this->unitPatuha);

    $this->actingAs($lain)
        ->get('/files/storage/'.$proof->struk_bbm_path)
        ->assertStatus(403);
});

it('mengizinkan Admin GA unit sendiri dan menolak Admin GA unit lain', function () {
    $owner = ($this->buatUser)('requester', $this->unitPatuha);
    $adminPatuha = ($this->buatUser)('general_affairs', $this->unitPatuha);
    $adminDieng = ($this->buatUser)('general_affairs', $this->unitDieng);
    $proof = ($this->buatBukti)($owner, $this->unitPatuha);

    $this->actingAs($adminPatuha)
        ->get('/files/storage/'.$proof->struk_bbm_path)
        ->assertStatus(200);

    $this->actingAs($adminDieng)
        ->get('/files/storage/'.$proof->struk_bbm_path)
        ->assertStatus(403);
});

it('membatasi endpoint transaction-photo ke unit user', function () {
    $owner = ($this->buatUser)('requester', $this->unitPatuha);
    $adminPatuha = ($this->buatUser)('general_affairs', $this->unitPatuha);
    $adminDieng = ($this->buatUser)('general_affairs', $this->unitDieng);
    $proof = ($this->buatBukti)($owner, $this->unitPatuha);

    $this->actingAs($adminPatuha)
        ->get(route('transaction.getPhoto', ['reportId' => $proof->id, 'type' => 'struk']))
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->actingAs($adminDieng)
        ->get(route('transaction.getPhoto', ['reportId' => $proof->id, 'type' => 'struk']))
        ->assertStatus(404);
});

it('tetap menyajikan file lama yang masih berada di disk publik (fallback)', function () {
    $owner = ($this->buatUser)('requester', $this->unitPatuha);
    $proof = ($this->buatBukti)($owner, $this->unitPatuha);

    // Simulasikan file historis: hanya ada di disk publik.
    Storage::disk('local')->delete($proof->struk_bbm_path);
    Storage::disk('public')->put($proof->struk_bbm_path, 'legacy-struk');

    $this->actingAs($owner)
        ->get('/files/storage/'.$proof->struk_bbm_path)
        ->assertStatus(200)
        ->assertSee('legacy-struk');
});

it('menyimpan struk hasil laporan operasional baru ke disk privat, bukan publik', function () {
    $owner = ($this->buatUser)('requester', $this->unitPatuha);
    $permohonan = ($this->buatPermohonan)($owner, $this->unitPatuha);

    $this->actingAs($owner)
        ->post(route('pelaporan.operational.store'), [
            'permohonan_id' => $permohonan->id,
            'purchase_date' => now()->format('Y-m-d'),
            'volume_bbm' => 30,
            'nominal' => 300000,
            'foto_struk' => UploadedFile::fake()->image('struk.jpg'),
            'struk_kategoris' => [0 => 'BBM'],
            'nominals' => [0 => 300000],
            'keterangan_s' => [0 => 'Struk BBM'],
            'metadata_s' => [0 => json_encode([
                'kategori' => 'BBM',
                'metadata' => ['nominal' => 300000],
                'text' => 'OCR',
            ])],
        ])
        ->assertSessionHas('success');

    $proof = $permohonan->fresh()->transactionProof;

    expect($proof)->not->toBeNull()
        ->and($proof->struk_bbm_path)->toContain('transaction_proofs/struk/')
        ->and(Storage::disk('local')->exists($proof->struk_bbm_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($proof->struk_bbm_path))->toBeFalse();
});

it('menyimpan struk dan odometer laporan BBM requester ke disk privat', function () {
    $owner = ($this->buatUser)('requester', $this->unitPatuha);
    $permohonan = ($this->buatPermohonan)($owner, $this->unitPatuha);

    \App\Models\Approved_Request::create([
        'permohonan_id' => $permohonan->id,
        'approval_date' => now()->subHour(),
        'voucher_code' => 'BBMGDE-PRIVACY-'.now()->format('dmy'),
        'valid_until' => now()->addHours(10),
        'approved_amount' => 300000,
        'authorizer_id' => $owner->id,
        'status' => 'active',
    ]);

    $this->actingAs($owner)
        ->post(route('requester.submit-report', $permohonan->id), [
            'purchase_date' => now()->format('Y-m-d'),
            'volume_bbm' => 20,
            'km_akhir' => 12000,
            'foto_struk' => UploadedFile::fake()->image('struk.jpg'),
            'foto_km' => UploadedFile::fake()->image('odometer.jpg'),
        ])
        ->assertSessionHas('success');

    $proof = $permohonan->fresh()->transactionProof;

    expect($proof)->not->toBeNull()
        ->and($proof->struk_bbm_path)->toContain('transaction_proofs/struk/')
        ->and($proof->odometer_photo_path)->toContain('transaction_proofs/odometer/')
        ->and(Storage::disk('local')->exists($proof->struk_bbm_path))->toBeTrue()
        ->and(Storage::disk('local')->exists($proof->odometer_photo_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($proof->struk_bbm_path))->toBeFalse()
        ->and(Storage::disk('public')->exists($proof->odometer_photo_path))->toBeFalse();
});

it('membatasi struk item SPD sesuai visibilitas surat', function () {
    $owner = ($this->buatUser)('requester', $this->unitPatuha);
    $lain = ($this->buatUser)('requester', $this->unitPatuha);
    $adminPatuha = ($this->buatUser)('general_affairs', $this->unitPatuha);

    $surat = SuratSpd::factory()->create([
        'user_id' => $owner->id,
        'unit_id' => $this->unitPatuha->id,
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    $path = 'transaction_proofs/struk/S.ITEM-'.uniqid().'.jpg';
    Storage::disk('local')->put($path, 'item-struk');

    StrukItem::create([
        'spd_id' => $surat->id,
        'nomor_surat_spd' => $surat->nomor_surat,
        'item_code' => 'S.BBM-'.$surat->nomor_surat.'-'.now()->format('d-m-Y').'-abcd',
        'path_image' => $path,
        'struk_kategori' => 'BBM',
        'kategori_source' => 'manual',
        'nominal' => 100000,
        'tanggal' => now()->format('Y-m-d'),
        'urutan' => 1,
    ]);

    $this->actingAs($owner)
        ->get('/files/storage/'.$path)
        ->assertStatus(200);

    $this->actingAs($lain)
        ->get('/files/storage/'.$path)
        ->assertStatus(403);

    $this->actingAs($adminPatuha)
        ->get('/files/storage/'.$path)
        ->assertStatus(200);
});

it('menyimpan QR surat di disk privat', function () {
    $owner = ($this->buatUser)('requester', $this->unitPatuha);
    $surat = SuratSpd::factory()->create([
        'user_id' => $owner->id,
        'unit_id' => $this->unitPatuha->id,
        'status' => 'approved',
        'approved_at' => now(),
        'pemberi_tugas_nama' => 'Manager Patuha',
    ]);

    $this->actingAs($owner)
        ->get(route('spd.surat.print', $surat))
        ->assertStatus(200);

    expect(Storage::disk('local')->allFiles('qr-surat'))->not->toBeEmpty()
        ->and(Storage::disk('public')->allFiles('qr-surat'))->toBeEmpty();
});

it('migrasi backfill memindahkan file ke privat dan bisa dibalik', function () {
    $migration = require database_path('migrations/2026_10_30_000001_move_sensitive_media_to_private_disk.php');

    $legacy = 'transaction_proofs/struk/S.LEGACY-'.uniqid().'.jpg';
    $qr = 'qr-surat/QR_LEGACY-'.uniqid().'.svg';

    Storage::disk('public')->put($legacy, 'legacy-struk');
    Storage::disk('public')->put($qr, '<svg></svg>');

    $migration->up();

    expect(Storage::disk('local')->exists($legacy))->toBeTrue()
        ->and(Storage::disk('local')->exists($qr))->toBeTrue()
        ->and(Storage::disk('public')->exists($legacy))->toBeFalse()
        ->and(Storage::disk('public')->exists($qr))->toBeFalse();

    $migration->down();

    expect(Storage::disk('public')->exists($legacy))->toBeTrue()
        ->and(Storage::disk('public')->exists($qr))->toBeTrue()
        ->and(Storage::disk('local')->exists($legacy))->toBeFalse()
        ->and(Storage::disk('local')->exists($qr))->toBeFalse();
});
