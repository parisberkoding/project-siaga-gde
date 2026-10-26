<?php

use App\Models\LpjPenggantian;
use App\Models\SpdDailyReport;
use App\Models\StrukItem;
use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->driver = User::factory()->create(['role' => 'requester']);
    $role = \App\Models\Role::where('code', 'requester')->first();
    if ($role) {
        $this->driver->roles()->attach($role->id, ['is_primary' => true]);
    }

    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $this->surat = SuratSpd::factory()->create([
        'user_id'           => $this->driver->id,
        'vehicle_id'        => $vehicle->id,
        'status'            => 'approved',
        'tanggal_berangkat' => now()->subDays(5),
        'tanggal_kembali'   => now()->subDays(2),
    ]);

    $this->daily = SpdDailyReport::create([
        'surat_spd_id' => $this->surat->id,
        'user_id'      => $this->driver->id,
        'tanggal'      => now()->subDays(3)->toDateString(),
        'status'       => 'draft',
    ]);

    $this->buatItem = function (string $kategori, float $nominal, ?string $tanggal): StrukItem {
        $item = StrukItem::create([
            'spd_id'              => $this->surat->id,
            'spd_daily_report_id' => $this->daily->id,
            'nomor_surat_spd'     => $this->surat->nomor_surat,
            'item_code'           => StrukItem::generateItemCode($kategori, $this->surat->nomor_surat, $tanggal),
            'path_image'          => 'transaction_proofs/struk/uji-' . uniqid() . '.jpg',
            'struk_kategori'      => $kategori,
            'kategori_source'     => 'ai',
            'nominal'             => $nominal,
            'tanggal'             => $tanggal,
            'keterangan'          => 'Hasil OCR',
            'urutan'              => 1,
        ]);

        return $item->fresh();
    };
});

it('mengizinkan driver memperbaiki tanggal hasil OCR per item', function () {
    // OCR salah baca: 2011 padahal 2026.
    $item = ($this->buatItem)('BBM', 700000, '2011-06-26');

    $this->actingAs($this->driver)
        ->patchJson(route('pelaporan.spd.daily.update-item', [$this->surat, $item]), [
            'nominal'        => 700000,
            'struk_kategori' => 'BBM',
            'tanggal'        => '2026-09-06',
            'keterangan'     => 'Pengisian BBM',
        ])
        ->assertOk()
        ->assertJson(['success' => true, 'groupKey' => '2026-09-06']);

    $item->refresh();

    expect($item->tanggal->format('Y-m-d'))->toBe('2026-09-06')
        ->and($item->kategori_source)->toBe('manual')
        ->and($item->edited_at)->not->toBeNull();
});

it('memindahkan item ke kelompok tanggal barunya di halaman validasi', function () {
    ($this->buatItem)('BBM', 700000, '2026-09-06');

    $this->actingAs($this->driver)
        ->get(route('pelaporan.spd.validasi', $this->surat))
        ->assertStatus(200)
        ->assertSee('Minggu, 06 September 2026', false)
        ->assertDontSee('Minggu, 26 Juni 2011', false);
});

it('menyimpan semua isian item sekaligus lewat endpoint bulk', function () {
    $a = ($this->buatItem)('BBM', 700000, '2011-06-26');
    $b = ($this->buatItem)('Tol', 50000, '2011-06-27');

    $this->actingAs($this->driver)
        ->patchJson(route('pelaporan.spd.daily.update-items', $this->surat), [
            'items' => [
                $a->slug => [
                    'tanggal'        => '2026-09-06',
                    'nominal'        => 750000,
                    'struk_kategori' => 'BBM',
                    'keterangan'     => 'Diperbaiki manual',
                ],
                $b->slug => [
                    'tanggal'        => '2026-09-07',
                    'nominal'        => 60000,
                    'struk_kategori' => 'Tol',
                    'keterangan'     => 'Diperbaiki manual',
                ],
            ],
        ])
        ->assertOk()
        ->assertJson(['success' => true, 'updated' => 2]);

    expect($a->fresh()->tanggal->format('Y-m-d'))->toBe('2026-09-06')
        ->and((float) $a->fresh()->nominal)->toBe(750000.0)
        ->and($b->fresh()->tanggal->format('Y-m-d'))->toBe('2026-09-07')
        ->and((float) $b->fresh()->nominal)->toBe(60000.0)
        ->and($b->fresh()->struk_kategori)->toBe('Tol');
});

it('menolak ubah tanggal/nominal yang tidak valid', function () {
    $item = ($this->buatItem)('BBM', 700000, '2011-06-26');

    $this->actingAs($this->driver)
        ->patchJson(route('pelaporan.spd.daily.update-item', [$this->surat, $item]), [
            'nominal'        => -1,
            'struk_kategori' => 'BBM',
            'tanggal'        => 'bukan-tanggal',
        ])
        ->assertStatus(422);
});

it('memblokir ubah item setelah LPJ dibuat', function () {
    $item = ($this->buatItem)('BBM', 700000, '2026-09-06');

    LpjPenggantian::create([
        'surat_spd_id' => $this->surat->id,
        'user_id'      => $this->driver->id,
        'vehicle_id'   => $this->surat->vehicle_id,
        'nomor_polisi' => 'D 1234 UJI',
        'nama'         => 'Driver Uji',
        'jabatan'      => 'Pengemudi',
        'uraian'       => 'Sudah final',
        'status'       => 'pending',
    ]);

    // Per-item
    $this->actingAs($this->driver)
        ->patchJson(route('pelaporan.spd.daily.update-item', [$this->surat, $item]), [
            'nominal'        => 700000,
            'struk_kategori' => 'BBM',
            'tanggal'        => '2026-09-07',
        ])
        ->assertStatus(422);

    // Bulk
    $this->actingAs($this->driver)
        ->patchJson(route('pelaporan.spd.daily.update-items', $this->surat), [
            'items' => [
                $item->slug => [
                    'tanggal'        => '2026-09-07',
                    'nominal'        => 700000,
                    'struk_kategori' => 'BBM',
                    'keterangan'     => 'x',
                ],
            ],
        ])
        ->assertStatus(422);

    expect($item->fresh()->tanggal->format('Y-m-d'))->toBe('2026-09-06');
});

it('menampilkan tombol Ubah Semua Isian di halaman validasi', function () {
    ($this->buatItem)('BBM', 700000, '2026-09-06');

    $this->actingAs($this->driver)
        ->get(route('pelaporan.spd.validasi', $this->surat))
        ->assertStatus(200)
        ->assertSee('Ubah Semua Isian', false)
        ->assertSee('Simpan Semua Perubahan', false)
        ->assertSee('Tanggal Struk', false);
});
