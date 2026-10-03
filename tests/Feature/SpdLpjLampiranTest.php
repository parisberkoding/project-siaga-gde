<?php

use App\Models\LpjPenggantian;
use App\Models\StrukItem;
use App\Models\SuratSpd;
use App\Models\SuratSpdBudget;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->buatUser = function (string $roleCode, string $legacyRole): User {
        $user = User::factory()->create(['role' => $legacyRole]);
        $role = \App\Models\Role::where('code', $roleCode)->first();

        if ($role) {
            $user->roles()->attach($role->id, ['is_primary' => true]);
        }

        return $user;
    };

    $this->buatLpj = function (User $pemilik, array $overrides = []): LpjPenggantian {
        $vehicle = VehicleAndToolsConsumers::factory()->create([
            'consumerial_type' => 'KENDARAAN',
            'is_approved'      => true,
            'is_active'        => true,
        ]);

        $surat = SuratSpd::factory()->create([
            'user_id'    => $pemilik->id,
            'vehicle_id' => $vehicle->id,
            'status'     => 'approved',
        ]);

        return LpjPenggantian::create(array_merge([
            'surat_spd_id'  => $surat->id,
            'user_id'       => $pemilik->id,
            'nama'          => 'Driver Uji',
            'jabatan'       => 'Pengemudi',
            'vehicle_id'    => $vehicle->id,
            'nomor_polisi'  => $vehicle->consumerial_code ?? 'B 1 ABC',
            'uraian'        => 'Perjalanan dinas uji',
            'uang_dari'     => 'KAS',
            'status'        => 'pending',
        ], $overrides));
    };
});

it('memisahkan kategori Makan dan Hotel pada normalisasi dan rekap per tanggal', function () {
    expect(StrukItem::normalizeKategori('Bon Restoran Padang'))->toBe('Makan');
    expect(StrukItem::normalizeKategori('Invoice Hotel Grand Preanger'))->toBe('Hotel');
    expect(StrukItem::normalizeKategori('Hotel + Makan'))->toBe('Makan');
    expect(StrukItem::normalizeKategori('Struk SPBU Pertamax'))->toBe('BBM');

    $driver = ($this->buatUser)('requester', 'requester');
    $lpj = ($this->buatLpj)($driver);

    $surat = $lpj->suratSpd;

    foreach ([
        ['kategori' => 'Makan', 'nominal' => 150000],
        ['kategori' => 'Hotel', 'nominal' => 500000],
        ['kategori' => 'Tol', 'nominal' => 50000],
    ] as $i => $row) {
        StrukItem::create([
            'spd_id'          => $surat->id,
            'nomor_surat_spd' => $surat->nomor_surat,
            'lpj_id'          => $lpj->id,
            'item_code'       => 'S.TEST-' . $surat->id . '-' . $i . '-' . uniqid(),
            'path_image'      => 'transaction_proofs/struk/uji.jpg',
            'struk_kategori'  => $row['kategori'],
            'kategori_source' => 'manual',
            'nominal'         => $row['nominal'],
            'tanggal'         => '2026-09-10',
            'urutan'          => $i + 1,
        ]);
    }

    $lpj->refresh()->load('strukItems');

    $spending = $lpj->getSpendingPerKategori();

    expect($spending)->toHaveKeys(['Tol', 'Parkir', 'BBM', 'Makan', 'Hotel', 'Lainnya'])
        ->and($spending['Makan'])->toBe(150000.0)
        ->and($spending['Hotel'])->toBe(500000.0)
        ->and($spending['Tol'])->toBe(50000.0);

    $rincian = $lpj->getRincianPerTanggal();

    expect($rincian)->toHaveKey('2026-09-10')
        ->and($rincian['2026-09-10']['Makan'])->toBe(150000.0)
        ->and($rincian['2026-09-10']['Hotel'])->toBe(500000.0)
        ->and($rincian['2026-09-10']['subtotal'])->toBe(700000.0);
});

it('menyertakan lembar pemberian dana hanya untuk General Affairs pada dokumen gabungan', function () {
    $driver = ($this->buatUser)('requester', 'requester');
    $ga = ($this->buatUser)('general_affairs', 'admin');

    $lpj = ($this->buatLpj)($driver);

    SuratSpdBudget::create([
        'surat_spd_id'     => $lpj->surat_spd_id,
        'tanggal'          => '2026-09-09',
        'nominal'          => 750000,
        'bentuk_pencairan' => 'Transfer',
        'bank_reference'   => 'TRF-0001',
        'rek_tujuan'       => 'BCA 1234567890',
        'urutan'           => 1,
    ]);

    $url = route('spd.laporan.export', $lpj);

    // Driver: dokumen tetap lengkap, tapi TANPA lembar pemberian dana.
    $this->actingAs($driver)
        ->get($url)
        ->assertStatus(200)
        ->assertSee('Lembar Ringkasan Penggunaan Biaya Perjalanan Dinas', false)
        ->assertSee('Penggantian Pertanggungjawaban', false)
        ->assertSee('Lampiran Penggunaan Dana', false)
        ->assertDontSee('class="pencairan"', false);

    // GA: lembar pemberian dana ikut disertakan.
    $this->actingAs($ga)
        ->get($url)
        ->assertStatus(200)
        ->assertSee('Lembar Lampiran Pemberian Dana Perjalanan Dinas', false)
        ->assertSee('class="pencairan"', false)
        ->assertSee('TRF-0001', false);
});

it('mengizinkan GA mengelola pencairan dan menyinkronkan jumlah diberikan pada LPJ', function () {
    $driver = ($this->buatUser)('requester', 'requester');
    $ga = ($this->buatUser)('general_affairs', 'admin');

    $lpj = ($this->buatLpj)($driver, ['total_pengeluaran' => 400000, 'jumlah_diberikan' => 0]);

    // Tambah pencairan tunai.
    $this->actingAs($ga)
        ->post(route('lpj.superadmin.pencairan.store', $lpj), [
            'tanggal'          => '2026-09-09',
            'nominal'          => 500000,
            'bentuk_pencairan' => 'Tunai',
        ])
        ->assertSessionHas('success');

    $pencairan = SuratSpdBudget::where('surat_spd_id', $lpj->surat_spd_id)->first();

    expect($pencairan)->not->toBeNull()
        ->and($pencairan->bentuk_pencairan)->toBe('Tunai')
        ->and((float) $lpj->fresh()->jumlah_diberikan)->toBe(500000.0)
        ->and((float) $lpj->fresh()->pengembalian)->toBe(100000.0)
        ->and((float) $lpj->fresh()->kekurangan)->toBe(0.0);

    // Ubah menjadi transfer + nominal baru.
    $this->actingAs($ga)
        ->put(route('lpj.superadmin.pencairan.update', [$lpj, $pencairan]), [
            'tanggal'          => '2026-09-09',
            'nominal'          => 300000,
            'bentuk_pencairan' => 'Transfer',
            'bank_reference'   => 'TRF-9999',
            'rek_tujuan'       => 'Mandiri 99887766',
        ])
        ->assertSessionHas('success');

    $pencairan->refresh();

    expect($pencairan->bentuk_pencairan)->toBe('Transfer')
        ->and($pencairan->bank_reference)->toBe('TRF-9999')
        ->and($pencairan->rek_tujuan)->toBe('Mandiri 99887766')
        ->and((float) $lpj->fresh()->jumlah_diberikan)->toBe(300000.0)
        ->and((float) $lpj->fresh()->kekurangan)->toBe(100000.0);

    // Hapus baris → jumlah diberikan kembali 0.
    $this->actingAs($ga)
        ->delete(route('lpj.superadmin.pencairan.destroy', [$lpj, $pencairan]))
        ->assertSessionHas('success');

    expect(SuratSpdBudget::where('surat_spd_id', $lpj->surat_spd_id)->count())->toBe(0)
        ->and((float) $lpj->fresh()->jumlah_diberikan)->toBe(0.0);
});

it('mewajibkan nomor referensi dan rekening tujuan untuk pencairan transfer', function () {
    $driver = ($this->buatUser)('requester', 'requester');
    $ga = ($this->buatUser)('general_affairs', 'admin');

    $lpj = ($this->buatLpj)($driver);

    $this->actingAs($ga)
        ->post(route('lpj.superadmin.pencairan.store', $lpj), [
            'tanggal'          => '2026-09-09',
            'nominal'          => 100000,
            'bentuk_pencairan' => 'Transfer',
        ])
        ->assertSessionHasErrors(['bank_reference', 'rek_tujuan']);

    expect(SuratSpdBudget::where('surat_spd_id', $lpj->surat_spd_id)->count())->toBe(0);
});

it('menolak driver mengakses pengelolaan dan unduhan lembar pemberian dana', function () {
    $driver = ($this->buatUser)('requester', 'requester');
    $lpj = ($this->buatLpj)($driver);

    $this->actingAs($driver)
        ->get(route('lpj.superadmin.pencairan.index', $lpj))
        ->assertStatus(403);

    $this->actingAs($driver)
        ->post(route('lpj.superadmin.pencairan.store', $lpj), [
            'tanggal'          => '2026-09-09',
            'nominal'          => 100000,
            'bentuk_pencairan' => 'Tunai',
        ])
        ->assertStatus(403);

    $this->actingAs($driver)
        ->get(route('spd.laporan.pemberian-dana', $lpj))
        ->assertStatus(403);
});

it('mengizinkan GA mengunduh lembar pemberian dana secara terpisah', function () {
    $driver = ($this->buatUser)('requester', 'requester');
    $ga = ($this->buatUser)('general_affairs', 'admin');

    $lpj = ($this->buatLpj)($driver);

    SuratSpdBudget::create([
        'surat_spd_id'     => $lpj->surat_spd_id,
        'tanggal'          => '2026-09-09',
        'nominal'          => 900000,
        'bentuk_pencairan' => 'Tunai',
        'urutan'           => 1,
    ]);

    $this->actingAs($ga)
        ->get(route('spd.laporan.pemberian-dana', $lpj))
        ->assertStatus(200)
        ->assertSee('Lembar Lampiran Pemberian Dana Perjalanan Dinas', false)
        ->assertSee('Rp. 900.000', false);
});

it('memakai format dokumen baru pada route print lama (template stub tidak dipakai lagi)', function () {
    $driver = ($this->buatUser)('requester', 'requester');
    $ga = ($this->buatUser)('general_affairs', 'admin');

    $lpj = ($this->buatLpj)($driver, ['status' => 'approved']);

    // Route khusus GA (permission lpj.validate).
    $this->actingAs($ga)
        ->get(route('lpj.superadmin.print', $lpj))
        ->assertStatus(200)
        ->assertSee('Lembar Ringkasan Penggunaan Biaya Perjalanan Dinas', false)
        ->assertSee('Lembar Persetujuan', false)
        ->assertSee('Lampiran Penggunaan Dana', false)
        ->assertDontSee('PEMBIAYAAN BIAYA JALAN TOL, BBM, PARKIR', false);

    // Route SPD lama (permission spd.view/spd.manage) — driver juga dapat format baru.
    $this->actingAs($driver)
        ->get(route('spd.laporan.print', $lpj))
        ->assertStatus(200)
        ->assertSee('Lembar Persetujuan', false)
        ->assertDontSee('PEMBIAYAAN BIAYA JALAN TOL, BBM, PARKIR', false);
});

it('tidak mencetak LPJ yang belum disetujui lewat route print lama', function () {
    $driver = ($this->buatUser)('requester', 'requester');
    $ga = ($this->buatUser)('general_affairs', 'admin');

    $lpj = ($this->buatLpj)($driver, ['status' => 'pending']);

    $this->actingAs($ga)
        ->get(route('lpj.superadmin.print', $lpj))
        ->assertStatus(404);
});
