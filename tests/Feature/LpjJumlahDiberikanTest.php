<?php

use App\Models\LpjPenggantian;
use App\Models\Role;
use App\Models\SpdDailyReport;
use App\Models\StrukItem;
use App\Models\SuratSpd;
use App\Models\SuratSpdBudget;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * BUG-LPJ-002 — "Jumlah Diberikan" harus terisi otomatis dari total pencairan
 * dana SPD (surat_spd_budgets) yang sudah disetujui; nilai tersimpan tidak
 * boleh tertimpa saat edit/tampil LPJ lama.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->requester = User::factory()->create([
        'role'         => 'requester',
        'nama_lengkap' => 'Driver LPJ QA',
    ]);
    $this->requester->roles()->attach(
        Role::where('code', 'requester')->firstOrFail()->id,
        ['is_primary' => true]
    );

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->superadmin->roles()->attach(
        Role::where('code', 'superadmin')->firstOrFail()->id,
        ['is_primary' => true]
    );

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $this->surat = SuratSpd::factory()->create([
        'user_id'           => $this->requester->id,
        'vehicle_id'        => $this->vehicle->id,
        'status'            => 'approved',
        'tanggal_berangkat' => now()->subDays(5),
        'tanggal_kembali'   => now()->subDays(2),
    ]);

    // Dua baris pencairan dana yang disetujui GA.
    SuratSpdBudget::create([
        'surat_spd_id'     => $this->surat->id,
        'tanggal'          => now()->subDays(5)->toDateString(),
        'nominal'          => 1000000,
        'bentuk_pencairan' => 'Transfer',
        'urutan'           => 1,
    ]);
    SuratSpdBudget::create([
        'surat_spd_id'     => $this->surat->id,
        'tanggal'          => now()->subDays(4)->toDateString(),
        'nominal'          => 500000,
        'bentuk_pencairan' => 'Tunai',
        'urutan'           => 2,
    ]);

    $this->daily = SpdDailyReport::create([
        'surat_spd_id' => $this->surat->id,
        'user_id'      => $this->requester->id,
        'tanggal'      => now()->subDays(5)->toDateString(),
        'status'       => 'submitted',
        'submitted_at' => now(),
    ]);

    StrukItem::create([
        'spd_id'              => $this->surat->id,
        'spd_daily_report_id' => $this->daily->id,
        'nomor_surat_spd'     => $this->surat->nomor_surat,
        'item_code'           => StrukItem::generateItemCode('BBM', $this->surat->nomor_surat, now()->toDateString()),
        'path_image'          => 'transaction_proofs/struk/test-lpj-002.jpg',
        'struk_kategori'      => 'BBM',
        'kategori_source'     => 'manual',
        'nominal'             => 470000,
        'tanggal'             => $this->daily->tanggal,
        'keterangan'          => '',
        'urutan'              => 1,
    ]);
});

it('mengisi Jumlah Diberikan dari total pencairan dana SPD saat finalize LPJ', function () {
    $this->actingAs($this->requester)
        ->post(route('pelaporan.spd.finalize', $this->surat), [
            'nama'    => $this->requester->nama_lengkap,
            'jabatan' => 'Pengemudi',
            'uraian'  => 'Perjalanan dinas QA',
        ])
        ->assertSessionHas('success');

    $lpj = LpjPenggantian::where('surat_spd_id', $this->surat->id)->firstOrFail();

    expect((float) $lpj->jumlah_diberikan)->toBe(1500000.0)
        // Pengembalian langsung dihitung: 1.500.000 - 470.000.
        ->and((float) $lpj->pengembalian)->toBe(1030000.0)
        ->and((float) $lpj->kekurangan)->toBe(0.0);
});

it('tidak mengisi Jumlah Diberikan saat SPD belum punya pencairan dana', function () {
    SuratSpdBudget::where('surat_spd_id', $this->surat->id)->delete();

    $this->actingAs($this->requester)
        ->post(route('pelaporan.spd.finalize', $this->surat), [
            'nama'    => $this->requester->nama_lengkap,
            'jabatan' => 'Pengemudi',
            'uraian'  => 'Perjalanan dinas tanpa pencairan',
        ])
        ->assertSessionHas('success');

    $lpj = LpjPenggantian::where('surat_spd_id', $this->surat->id)->firstOrFail();

    expect((float) $lpj->jumlah_diberikan)->toBe(0.0);
});

it('memakai nilai efektif (pencairan) pada ringkasan detail LPJ lama yang masih 0', function () {
    $lpj = LpjPenggantian::create([
        'surat_spd_id'      => $this->surat->id,
        'user_id'           => $this->requester->id,
        'vehicle_id'        => $this->vehicle->id,
        'nama'              => 'Driver LPJ Lama',
        'jabatan'           => 'Pengemudi',
        'nomor_polisi'      => $this->vehicle->consumerial_code,
        'uraian'            => 'LPJ lama sebelum perbaikan',
        'uang_dari'         => 'KAS',
        'jumlah_diberikan'  => 0,
        'total_pengeluaran' => 470000,
        'status'            => 'pending',
    ]);

    $this->actingAs($this->superadmin)
        ->get(route('lpj.superadmin.show', $lpj))
        ->assertOk()
        ->assertSee('Terisi otomatis dari total pencairan dana SPD', false)
        ->assertSee('Rp 1.500.000', false)
        ->assertSee('Rp 1.030.000', false);

    // Nilai tersimpan di DB tidak diubah hanya karena halaman dibuka.
    expect((float) $lpj->fresh()->jumlah_diberikan)->toBe(0.0);
});

it('tidak menimpa Jumlah Diberikan yang sudah tersimpan saat membuka detail LPJ', function () {
    $lpj = LpjPenggantian::create([
        'surat_spd_id'      => $this->surat->id,
        'user_id'           => $this->requester->id,
        'vehicle_id'        => $this->vehicle->id,
        'nama'              => 'Driver LPJ Terisi',
        'jabatan'           => 'Pengemudi',
        'nomor_polisi'      => $this->vehicle->consumerial_code,
        'uraian'            => 'LPJ sudah dikoreksi GA',
        'uang_dari'         => 'KAS',
        'jumlah_diberikan'  => 900000,
        'total_pengeluaran' => 470000,
        'status'            => 'pending',
    ]);

    $this->actingAs($this->superadmin)
        ->get(route('lpj.superadmin.show', $lpj))
        ->assertOk()
        ->assertSee('value="900000"', false)
        ->assertDontSee('value="1500000"', false);
});
