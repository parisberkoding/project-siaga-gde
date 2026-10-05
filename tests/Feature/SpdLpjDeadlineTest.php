<?php

use App\Models\LpjPenggantian;
use App\Models\Role;
use App\Models\SpdDailyReport;
use App\Models\StrukItem;
use App\Models\SuratSpd;
use App\Models\SuratSpdDetail;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->buatUser = function (string $roleCode, string $legacyRole): User {
        $user = User::factory()->create(['role' => $legacyRole]);
        $role = Role::where('code', $roleCode)->first();

        if ($role) {
            $user->roles()->attach($role->id, ['is_primary' => true]);
        }

        return $user->fresh();
    };

    $this->buatSuratOverdue = function (User $driver, array $overrides = []): SuratSpd {
        $vehicle = VehicleAndToolsConsumers::factory()->create([
            'consumerial_type' => 'KENDARAAN',
            'is_approved'      => true,
            'is_active'        => true,
        ]);

        // SPD selesai 10 hari lalu → batas submit (kembali + 3 hari) sudah lewat.
        return SuratSpd::factory()->create(array_merge([
            'user_id'           => $driver->id,
            'vehicle_id'        => $vehicle->id,
            'status'            => 'approved',
            'tanggal_berangkat' => now()->subDays(13)->toDateString(),
            'tanggal_kembali'   => now()->subDays(10)->toDateString(),
            'lpj_deadline'      => now()->subDays(7)->toDateString(),
        ], $overrides));
    };
});

it('menandai surat sebagai overdue tanpa menutup form LPJ', function () {
    $driver = ($this->buatUser)('requester', 'requester');
    $surat = ($this->buatSuratOverdue)($driver);

    expect($surat->menunggu_lpj)->toBeTrue()
        ->and($surat->isLpjDeadlineOverdue())->toBeTrue()
        ->and($surat->lpj_terlambat)->toBeTrue()
        ->and($surat->lpj_overdue_days)->toBeGreaterThanOrEqual(7);

    // Form LPJ tetap bisa DIBUKA.
    $this->actingAs($driver)
        ->get(route('requester.lpj.create', $surat))
        ->assertStatus(200);

    // Halaman validasi juga tetap terbuka + menampilkan penanda terlambat.
    $this->actingAs($driver)
        ->get(route('pelaporan.spd.validasi', $surat))
        ->assertStatus(200)
        ->assertSee('LPJ sudah melewati batas submit', false)
        ->assertSee('tetap terbuka', false);

    // SPD Berjalan menandai surat ini, bukan menyembunyikannya.
    $this->actingAs($driver)
        ->get(route('requester.spd.berjalan'))
        ->assertStatus(200)
        ->assertSee($surat->nomor_surat, false)
        ->assertSee('LPJ Terlambat', false);
});

it('tetap mengizinkan finalisasi LPJ setelah lewat deadline dan mencatatnya terlambat', function () {
    $driver = ($this->buatUser)('requester', 'requester');
    $surat = ($this->buatSuratOverdue)($driver);

    $report = SpdDailyReport::create([
        'surat_spd_id' => $surat->id,
        'user_id'      => $driver->id,
        'tanggal'      => now()->subDays(11)->toDateString(),
        'status'       => 'submitted',
        'submitted_at' => now()->subDays(11),
    ]);

    StrukItem::create([
        'spd_id'             => $surat->id,
        'spd_daily_report_id' => $report->id,
        'nomor_surat_spd'    => $surat->nomor_surat,
        'item_code'          => 'S.TOL-DEADLINE-' . uniqid(),
        'path_image'         => 'transaction_proofs/struk/uji.jpg',
        'struk_kategori'     => 'Tol',
        'kategori_source'    => 'manual',
        'nominal'            => 50000,
        'tanggal'            => now()->subDays(11)->toDateString(),
        'urutan'             => 1,
    ]);

    // Form finalisasi TIDAK ditutup oleh deadline — submit tetap diterima.
    $this->actingAs($driver)
        ->post(route('pelaporan.spd.finalize', $surat), [
            'nama'    => 'Driver Uji',
            'jabatan' => 'Pengemudi',
            'uraian'  => 'Perjalanan terlambat',
        ])
        ->assertRedirect();

    $lpj = LpjPenggantian::where('surat_spd_id', $surat->id)->first();

    expect($lpj)->not->toBeNull()
        ->and($lpj->is_late)->toBeTrue()
        ->and($lpj->days_late)->toBeGreaterThanOrEqual(1);
});

it('menghitung batas submit dari tanggal_kembali + 3 hari bila lpj_deadline kosong', function () {
    $driver = ($this->buatUser)('requester', 'requester');

    $surat = ($this->buatSuratOverdue)($driver, [
        'lpj_deadline' => null,
    ]);

    expect($surat->lpj_deadline_efektif->toDateString())->toBe(now()->subDays(7)->toDateString())
        ->and($surat->isLpjDeadlineOverdue())->toBeTrue();
});

it('menganggap submit pada hari deadline masih tepat waktu', function () {
    $driver = ($this->buatUser)('requester', 'requester');

    $surat = ($this->buatSuratOverdue)($driver, [
        'tanggal_kembali' => now()->subDays(3)->toDateString(),
        'lpj_deadline'    => now()->toDateString(), // hari ini = hari deadline
    ]);

    expect($surat->isLpjDeadlineOverdue())->toBeFalse()
        ->and($surat->lpj_terlambat)->toBeFalse()
        ->and($surat->lpj_overdue_days)->toBe(0);
});

it('memberi notice overdue ke GA dan ke User yang berangkat', function () {
    $driver = ($this->buatUser)('requester', 'requester');
    $ga = ($this->buatUser)('general_affairs', 'admin');
    $pegawai = ($this->buatUser)('pegawai', 'pegawai');

    $surat = ($this->buatSuratOverdue)($driver);

    // Pegawai ikut perjalanan ini sebagai pemakai.
    SuratSpdDetail::create([
        'surat_spd_id' => $surat->id,
        'nama_lengkap' => $pegawai->nama_lengkap,
        'user_id'      => $pegawai->id,
        'urutan'       => 1,
    ]);

    // GA melihat notice di daftar validasi LPJ.
    $this->actingAs($ga)
        ->get(route('lpj.superadmin.index'))
        ->assertStatus(200)
        ->assertSee('belum dibuatkan LPJ', false)
        ->assertSee($surat->nomor_surat, false);

    // User yang berangkat juga melihat notice-nya.
    $this->actingAs($pegawai)
        ->get(route('lpj.pengguna.index'))
        ->assertStatus(200)
        ->assertSee('belum dibuatkan LPJ', false)
        ->assertSee($surat->nomor_surat, false);

    // GA juga melihat penanda di Laporan SPD.
    $this->actingAs($ga)
        ->get(route('surat.superadmin.report'))
        ->assertStatus(200)
        ->assertSee('Menunggu LPJ', false)
        ->assertSee('lewat batas 3 hari', false);
});

it('tidak menampilkan notice overdue untuk surat yang belum lewat batas', function () {
    $driver = ($this->buatUser)('requester', 'requester');
    $ga = ($this->buatUser)('general_affairs', 'admin');

    // SPD baru selesai kemarin → batas masih 2 hari lagi.
    $surat = ($this->buatSuratOverdue)($driver, [
        'tanggal_berangkat' => now()->subDays(3)->toDateString(),
        'tanggal_kembali'   => now()->subDays(1)->toDateString(),
        'lpj_deadline'      => now()->addDays(2)->toDateString(),
    ]);

    expect($surat->menunggu_lpj)->toBeTrue()
        ->and($surat->lpj_terlambat)->toBeFalse();

    $this->actingAs($ga)
        ->get(route('lpj.superadmin.index'))
        ->assertStatus(200)
        ->assertDontSee('belum dibuatkan LPJ', false);
});
