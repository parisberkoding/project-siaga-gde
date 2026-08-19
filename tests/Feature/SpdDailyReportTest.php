<?php

use App\Models\LpjPenggantian;
use App\Models\SpdDailyReport;
use App\Models\StrukItem;
use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->user = User::factory()->create(['role' => 'requester']);
    $role = \App\Models\Role::where('code', 'requester')->first();
    if ($role) {
        $this->user->roles()->attach($role->id, ['is_primary' => true]);
    }
});

function makeSuratFor(User $user, bool $selesai = true): SuratSpd
{
    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $berangkat = $selesai ? now()->subDays(5) : now()->subDays(2);
    $kembali = $selesai ? now()->subDays(2) : now()->addDays(3);

    return SuratSpd::factory()->create([
        'user_id'          => $user->id,
        'vehicle_id'       => $vehicle->id,
        'status'           => 'approved',
        'tanggal_berangkat' => $berangkat,
        'tanggal_kembali'  => $kembali,
    ]);
}

function tambahItemHarian(SpdDailyReport $daily, string $kategori, float $nominal): StrukItem
{
    return StrukItem::create([
        'spd_id'              => $daily->surat_spd_id,
        'spd_daily_report_id' => $daily->id,
        'nomor_surat_spd'     => $daily->suratSpd->nomor_surat,
        'item_code'           => StrukItem::generateItemCode($kategori, $daily->suratSpd->nomor_surat, now()->toDateString()),
        'path_image'          => 'transaction_proofs/struk/test-' . uniqid() . '.jpg',
        'struk_kategori'      => $kategori,
        'kategori_source'     => 'manual',
        'nominal'             => $nominal,
        'tanggal'             => $daily->tanggal,
        'keterangan'          => '',
        'urutan'              => $daily->strukItems()->count() + 1,
    ]);
}

it('membuat draft server-side saat upload item (fix refresh hilang)', function () {
    $surat = makeSuratFor($this->user);

    $this->actingAs($this->user)
        ->post(route('pelaporan.spd.daily.upload-item', $surat), [
            'surat_spd_id' => $surat->id,
            'tanggal'      => now()->format('Y-m-d'),
            'struk'        => \Illuminate\Http\UploadedFile::fake()->image('struk.jpg'),
        ])
        ->assertJson(['success' => true]);

    // Draft dibuat otomatis + 1 struk item ter-link
    expect(SpdDailyReport::where('surat_spd_id', $surat->id)->draft()->count())->toBe(1);
    expect(StrukItem::whereNotNull('spd_daily_report_id')->count())->toBe(1);
});

it('mengirim laporan harian (status submitted)', function () {
    $surat = makeSuratFor($this->user);

    $this->actingAs($this->user)
        ->post(route('pelaporan.spd.daily.store', $surat), [
            'surat_spd_id' => $surat->id,
            'tanggal'      => now()->format('Y-m-d'),
            'uraian'       => 'Monitoring PLTP Patuha',
            'catatan'      => '',
        ])
        ->assertSessionHas('success');

    $daily = SpdDailyReport::where('surat_spd_id', $surat->id)->first();
    expect($daily)->not->toBeNull();
    expect($daily->status)->toBe('submitted');
    expect($daily->submitted_at)->not->toBeNull();
});

it('menampilkan halaman validasi dengan semua file yang dikirim driver', function () {
    $surat = makeSuratFor($this->user);

    $daily = SpdDailyReport::create([
        'surat_spd_id'  => $surat->id,
        'user_id'       => $this->user->id,
        'tanggal'       => now()->subDays(1)->toDateString(),
        'status'        => 'submitted',
        'submitted_at'  => now(),
    ]);
    $item = tambahItemHarian($daily, 'BBM', 350000);
    tambahItemHarian($daily, 'Tambahan', 0);

    $this->actingAs($this->user)
        ->get(route('pelaporan.spd.validasi', $surat))
        ->assertStatus(200)
        ->assertSee($item->item_code, false)
        ->assertSee('Submit sebagai LPJ', false);
});

it('membuat LPJ final dari laporan harian saat finalize', function () {
    $surat = makeSuratFor($this->user);

    // 2 laporan harian submitted
    $d1 = SpdDailyReport::create([
        'surat_spd_id'  => $surat->id,
        'user_id'       => $this->user->id,
        'tanggal'       => now()->subDays(2)->toDateString(),
        'status'        => 'submitted',
        'submitted_at'  => now(),
    ]);
    tambahItemHarian($d1, 'BBM', 400000);
    tambahItemHarian($d1, 'Tol', 50000);

    $d2 = SpdDailyReport::create([
        'surat_spd_id'  => $surat->id,
        'user_id'       => $this->user->id,
        'tanggal'       => now()->subDays(1)->toDateString(),
        'status'        => 'submitted',
        'submitted_at'  => now(),
    ]);
    tambahItemHarian($d2, 'Parkir', 20000);

    $this->actingAs($this->user)
        ->post(route('pelaporan.spd.finalize', $surat), [
            'nama'    => $this->user->nama_lengkap,
            'jabatan' => 'Pengemudi',
            'uraian'  => 'Perjalanan dinas monitoring',
        ])
        ->assertSessionHas('success');

    $lpj = LpjPenggantian::where('surat_spd_id', $surat->id)->first();
    expect($lpj)->not->toBeNull();
    expect($lpj->status)->toBe('pending');
    expect((float) $lpj->total_pengeluaran)->toBe(470000.0);

    // Detail dibuat per item keuangan (3 detail)
    expect($lpj->details()->count())->toBe(3);

    // Semua struk_items ter-link ke LPJ
    expect($lpj->strukItems()->count())->toBe(3);
});

it('memblokir finalize sebelum tanggal selesai SPD', function () {
    $surat = makeSuratFor($this->user, selesai: false);

    $daily = SpdDailyReport::create([
        'surat_spd_id'  => $surat->id,
        'user_id'       => $this->user->id,
        'tanggal'       => now()->toDateString(),
        'status'        => 'submitted',
        'submitted_at'  => now(),
    ]);
    tambahItemHarian($daily, 'BBM', 100000);

    $this->actingAs($this->user)
        ->post(route('pelaporan.spd.finalize', $surat), [
            'nama' => $this->user->nama_lengkap,
        ])
        ->assertSessionHas('error');

    expect(LpjPenggantian::where('surat_spd_id', $surat->id)->count())->toBe(0);
});

it('membutuhkan minimal 1 item pengeluaran saat finalize', function () {
    $surat = makeSuratFor($this->user);

    // Laporan harian hanya berisi foto tambahan (tidak ada pengeluaran)
    $daily = SpdDailyReport::create([
        'surat_spd_id'  => $surat->id,
        'user_id'       => $this->user->id,
        'tanggal'       => now()->subDays(1)->toDateString(),
        'status'        => 'submitted',
        'submitted_at'  => now(),
    ]);
    tambahItemHarian($daily, 'Tambahan', 0);

    $this->actingAs($this->user)
        ->post(route('pelaporan.spd.finalize', $surat), [
            'nama' => $this->user->nama_lengkap,
        ])
        ->assertSessionHas('error');

    expect(LpjPenggantian::where('surat_spd_id', $surat->id)->count())->toBe(0);
});

it('menolak bulk upload > 5 gambar sekaligus (FLOW BARU: maks 5 per kirim)', function () {
    $surat = makeSuratFor($this->user);

    $this->actingAs($this->user)
        ->postJson(route('pelaporan.spd.daily.upload-items', $surat), [
            'surat_spd_id' => $surat->id,
            'tanggal'      => now()->format('Y-m-d'),
            'images'       => [
                \Illuminate\Http\UploadedFile::fake()->image('a.jpg'),
                \Illuminate\Http\UploadedFile::fake()->image('b.jpg'),
                \Illuminate\Http\UploadedFile::fake()->image('c.jpg'),
                \Illuminate\Http\UploadedFile::fake()->image('d.jpg'),
                \Illuminate\Http\UploadedFile::fake()->image('e.jpg'),
                \Illuminate\Http\UploadedFile::fake()->image('f.jpg'),
            ],
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    expect(StrukItem::where('spd_id', $surat->id)->count())->toBe(0);
});
