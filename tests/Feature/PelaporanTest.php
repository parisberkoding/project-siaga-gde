<?php

use App\Models\LpjPenggantian;
use App\Models\Permohonan;
use App\Models\Site;
use App\Models\StrukItem;
use App\Models\SuratSpd;
use App\Models\Transaction_Proofs;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleDetail;
use App\Models\VehicleTripAnalysis;
use Illuminate\Http\UploadedFile;

// NB: RBAC tables & permission seeds dibutuhkan. Memakai seeder + RefreshDatabase.
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->admin = User::factory()->create(['role' => 'superadmin']);
    $role = \App\Models\Role::where('code', 'superadmin')->first();
    if ($role) {
        $this->admin->roles()->attach($role->id, ['is_primary' => true]);
    }
});

it('melayani GET /pelaporan untuk user dengan permission', function () {
    $this->actingAs($this->admin)
        ->get(route('pelaporan.index'))
        ->assertStatus(200)
        ->assertSee('Pelaporan BBM', false);
});

it('menyimpan LPJ SPD per item struk + struk_items', function () {
    // Butuh site untuk SuratSpd? Cukup buat minimal.
    $user = $this->admin;
    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);
    VehicleDetail::factory()->create([
        'vehicle_and_tools_consumer_id' => $vehicle->id,
        'nominal_full_tank'             => 500000,
    ]);

    $surat = SuratSpd::factory()->create([
        'user_id'    => $user->id,
        'vehicle_id' => $vehicle->id,
        'status'     => 'approved',
    ]);

    $image = UploadedFile::fake()->image('struk.jpg', 400, 600);

    $this->actingAs($user)
        ->post(route('pelaporan.spd.store'), [
            'surat_spd_id'      => $surat->id,
            'nama'              => 'Test Driver',
            'jabatan'           => 'Pengemudi',
            'uraian'            => 'Perjalanan dinas uji',
            'uang_dari'         => 'KAS',
            'jumlah_diberikan'  => 500000,
            'catatan'           => 'test',
            'items'             => [
                [
                    'tanggal'         => now()->format('Y-m-d'),
                    'struk_kategori'  => 'BBM',
                    'nominal'         => 300000,
                    'keterangan'      => 'SPBU',
                    'kategori_source' => 'ai',
                    'metadata_json'   => '{"kategori":"BBM"}',
                    'struk'           => $image,
                ],
            ],
        ])
        ->assertSessionHas('success');

    $lpj = LpjPenggantian::where('surat_spd_id', $surat->id)->first();
    expect($lpj)->not->toBeNull();
    expect($lpj->details()->count())->toBe(1);
    expect($lpj->strukItems()->count())->toBe(1);

    $si = $lpj->strukItems()->first();
    expect($si->struk_kategori)->toBe('BBM');
    expect($si->kategori_source)->toBe('ai');
    expect($si->item_code)->toStartWith('S.BBM-');
    expect(StrukItem::where('item_code', $si->item_code)->exists())->toBeTrue();
});

it('menyimpan laporan BBM operasional ke transaction_proofs', function () {
    $user = $this->admin;
    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $permohonan = Permohonan::factory()->create([
        'requester_id' => $user->id,
        'consumerial_tools_id' => $vehicle->id,
        'status'       => 'approved',
    ]);

    $this->actingAs($user)
        ->post(route('pelaporan.operational.store'), [
            'permohonan_id' => $permohonan->id,
            'purchase_date' => now()->format('Y-m-d'),
            'volume_bbm'    => 30.5,
            'nominal'       => 300000,
            'foto_struk'    => UploadedFile::fake()->image('struk.jpg'),
            'struk_kategoris' => [0 => 'BBM'],
            'nominals'        => [0 => 300000],
            'keterangan_s'    => [0 => 'Struk BBM operasional'],
            'metadata_s'      => [0 => json_encode(['kategori' => 'BBM', 'metadata' => ['nominal' => 300000, 'volume' => 30.5], 'text' => 'OCR'])],
        ])
        ->assertSessionHas('success');

    expect($permohonan->fresh()->status)->toBe('completed');
    expect($permohonan->transactionProof)->not->toBeNull();
    $proof = $permohonan->transactionProof;
    expect($proof->struk_bbm_path)->toContain('transaction_proofs/struk/');
});

it('menyimpan laporan BBM operasional via foto_files[] tanpa foto_struk (fix struk_bbm_path null)', function () {
    $user = User::factory()->create(['role' => 'requester']);
    $role = \App\Models\Role::where('code', 'requester')->first();
    if ($role) {
        $user->roles()->attach($role->id, ['is_primary' => true]);
    }

    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $permohonan = Permohonan::factory()->create([
        'requester_id' => $user->id,
        'consumerial_tools_id' => $vehicle->id,
        'status'       => 'approved',
        'bill_amounts' => 400000,
    ]);

    $this->actingAs($user)
        ->post(route('pelaporan.operational.store'), [
            'permohonan_id'   => $permohonan->id,
            'purchase_date'   => now()->format('Y-m-d'),
            'volume_bbm'      => 40.0,
            'nominal'         => 400000,
            'foto_files'      => [
                UploadedFile::fake()->image('struk-bbm.jpg'),
                UploadedFile::fake()->image('odometer.jpg'),
            ],
            'struk_kategoris' => [0 => 'BBM', 1 => 'Tambahan'],
            'nominals'        => [0 => 400000, 1 => 0],
            'keterangan_s'    => [0 => 'Struk BBM', 1 => 'Foto odometer'],
            'metadata_s'      => [0 => json_encode(['kategori' => 'BBM', 'metadata' => ['nominal' => 400000], 'text' => 'OCR']), 1 => json_encode(['kategori' => 'Tambahan', 'metadata' => [], 'text' => ''])],
        ])
        ->assertSessionHas('success');

    expect($permohonan->fresh()->status)->toBe('completed');

    $proof = $permohonan->transactionProof;
    expect($proof)->not->toBeNull();
    expect($proof->struk_bbm_path)->not->toBeNull();
    expect($proof->struk_bbm_path)->toContain('transaction_proofs/struk/');

    // 2 file → 2 struk_items
    expect(\App\Models\StrukItem::where('nomor_surat_spd', null)->count())->toBe(2);
});

it('menolak laporan BBM operasional bila nominal struk tidak sesuai permohonan', function () {
    $user = $this->admin;
    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $permohonan = Permohonan::factory()->create([
        'requester_id'         => $user->id,
        'consumerial_tools_id' => $vehicle->id,
        'status'               => 'approved',
        'bill_amounts'         => 300000,
    ]);

    $this->actingAs($user)
        ->from(route('pelaporan.index'))
        ->post(route('pelaporan.operational.store'), [
            'permohonan_id'   => $permohonan->id,
            'purchase_date'   => now()->format('Y-m-d'),
            'volume_bbm'      => 30,
            'nominal'         => 150000,
            'foto_files'      => [UploadedFile::fake()->image('struk.jpg')],
            'struk_kategoris' => [0 => 'BBM'],
            'nominals'        => [0 => 150000],
            'keterangan_s'    => [0 => 'Struk BBM'],
            'metadata_s'      => [0 => json_encode(['kategori' => 'BBM', 'metadata' => ['nominal' => 150000], 'text' => 'OCR'])],
        ])
        ->assertSessionHas('error');

    expect($permohonan->fresh()->status)->toBe('approved');
    expect($permohonan->transactionProof)->toBeNull();
});

it('menerima laporan BBM operasional bila nominal dalam toleransi 1%', function () {
    $user = $this->admin;
    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $permohonan = Permohonan::factory()->create([
        'requester_id'         => $user->id,
        'consumerial_tools_id' => $vehicle->id,
        'status'               => 'approved',
        'bill_amounts'         => 300000,
    ]);

    $this->actingAs($user)
        ->post(route('pelaporan.operational.store'), [
            'permohonan_id'   => $permohonan->id,
            'purchase_date'   => now()->format('Y-m-d'),
            'volume_bbm'      => 30,
            'nominal'         => 301500,
            'foto_files'      => [UploadedFile::fake()->image('struk.jpg')],
            'struk_kategoris' => [0 => 'BBM'],
            'nominals'        => [0 => 301500],
            'keterangan_s'    => [0 => 'Struk BBM'],
            'metadata_s'      => [0 => json_encode(['kategori' => 'BBM', 'metadata' => ['nominal' => 301500], 'text' => 'OCR'])],
        ])
        ->assertSessionHas('success');

    expect($permohonan->fresh()->status)->toBe('completed');
});

it('menolak laporan BBM operasional bila plat nomor struk berbeda', function () {
    $user = $this->admin;
    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'consumerial_code' => 'B 1234 XYZ',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $permohonan = Permohonan::factory()->create([
        'requester_id'         => $user->id,
        'consumerial_tools_id' => $vehicle->id,
        'status'               => 'approved',
        'bill_amounts'         => 300000,
    ]);

    $this->actingAs($user)
        ->from(route('pelaporan.index'))
        ->post(route('pelaporan.operational.store'), [
            'permohonan_id'   => $permohonan->id,
            'purchase_date'   => now()->format('Y-m-d'),
            'volume_bbm'      => 30,
            'nominal'         => 300000,
            'foto_files'      => [UploadedFile::fake()->image('struk.jpg')],
            'struk_kategoris' => [0 => 'BBM'],
            'nominals'        => [0 => 300000],
            'keterangan_s'    => [0 => 'Struk BBM'],
            'metadata_s'      => [0 => json_encode(['kategori' => 'BBM', 'metadata' => ['nominal' => 300000, 'plat_nomor' => 'D9999ZZ'], 'text' => 'OCR'])],
        ])
        ->assertSessionHas('error');

    expect($permohonan->fresh()->status)->toBe('approved');
});

it('mengembalikan kategorisasi AI pada /pelaporan/analyze-struk (fallback)', function () {
    $this->actingAs($this->admin)
        ->post(route('pelaporan.analyze-struk'), [
            'image' => UploadedFile::fake()->image('struk.png', 400, 600),
        ])
        ->assertStatus(200)
        ->assertJson(['success' => true]);
});

it('menggabungkan BBM SPD (struk_items LPJ approved) ke analisis trip', function () {
    $user = $this->admin;
    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    // Dua permohonan BBM operasional completed → dua titik odometer
    $p1 = Permohonan::factory()->create([
        'requester_id'        => $user->id,
        'consumerial_tools_id' => $vehicle->id,
        'status'              => 'completed',
        'bill_amounts'        => 100000, // 10 liter @ Rp10.000
    ]);
    $p2 = Permohonan::factory()->create([
        'requester_id'        => $user->id,
        'consumerial_tools_id' => $vehicle->id,
        'status'              => 'completed',
        'bill_amounts'        => 200000,
    ]);

    $proof1 = Transaction_Proofs::create([
        'transaction_id'    => 1,
        'req_id'            => $p1->id,
        'requester_id'      => $user->id,
        'voucher_number'    => 'V-TRIP-1',
        'purchase_datetime' => now()->subDays(10),
        'fuel_volume'       => 10,
        'km_terakhir'       => 1000,
        'struk_bbm_path'    => 'transaction_proofs/struk/v-trip-1.jpg',
    ]);
    $proof2 = Transaction_Proofs::create([
        'transaction_id'    => 1,
        'req_id'            => $p2->id,
        'requester_id'      => $user->id,
        'voucher_number'    => 'V-TRIP-2',
        'purchase_datetime' => now()->subDays(5),
        'fuel_volume'       => 20,
        'km_terakhir'       => 1400,
        'struk_bbm_path'    => 'transaction_proofs/struk/v-trip-2.jpg',
    ]);

    // SPD + LPJ approved + struk BBM di dalam rentang waktu pengukuran
    $surat = SuratSpd::factory()->create([
        'user_id'    => $user->id,
        'vehicle_id' => $vehicle->id,
        'status'     => 'approved',
    ]);

    $lpj = LpjPenggantian::create([
        'lpj_number'   => 'LPJ-TRIP-TEST',
        'surat_spd_id' => $surat->id,
        'user_id'      => $user->id,
        'nama'         => 'Test Driver',
        'jabatan'      => 'Pengemudi',
        'vehicle_id'   => $vehicle->id,
        'nomor_polisi' => 'D 0000 XX',
        'uraian'       => 'Perjalanan dinas',
        'status'       => 'approved',
    ]);

    StrukItem::create([
        'spd_id'          => $surat->id,
        'nomor_surat_spd' => $surat->nomor_surat,
        'lpj_id'          => $lpj->id,
        'item_code'       => StrukItem::generateItemCode('BBM', $surat->nomor_surat),
        'path_image'      => 'struk_items/spd/' . $surat->id . '/test.jpg',
        'struk_kategori'  => 'BBM',
        'kategori_source' => 'manual',
        'nominal'         => 50000, // ≈ 5 liter @ Rp10.000 (dari riwayat kendaraan)
        'tanggal'         => now()->subDays(7),
        'urutan'          => 1,
    ]);

    $controller = app(\App\Http\Controllers\SuperadminController::class);
    $controller->generateTripAnalysis();

    $analysis = VehicleTripAnalysis::where('transaction_proof_start_id', $proof1->id)
        ->where('transaction_proof_end_id', $proof2->id)
        ->first();

    expect($analysis)->not->toBeNull();
    expect((float) $analysis->km_difference)->toBe(400.0);
    // 10 L operasional + 5 L SPD = 15 L total
    expect((float) $analysis->fuel_consumed)->toBe(15.0);
    expect((float) $analysis->fuel_efficiency)->toBe(round(400 / 15, 2));
    expect($analysis->system_notes)->toContain('BBM SPD');
});
