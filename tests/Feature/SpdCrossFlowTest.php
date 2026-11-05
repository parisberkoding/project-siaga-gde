<?php

use App\Models\EmployeeDefault;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\SuratSpdBudget;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Integrasi lintas flow SPD ↔ BBM serta perubahan POV driver:
 *  - kendaraan yang dipakai SPD aktif tidak boleh dipakai BBM operasional;
 *  - driver melihat surat SPD yang ditugaskan lewat Master Pekerja;
 *  - pemberi tugas mengikuti Master Pekerja;
 *  - perpanjangan tidak menambah budget otomatis, Admin GA mencatat dana.
 */
beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->unit = Unit::create(['code' => 'PTH', 'name' => 'Unit Patuha']);

    $this->buatUser = function (string $roleCode, string $legacyRole): User {
        $user = User::factory()->create([
            'role' => $legacyRole,
            'unit_id' => $this->unit->id,
        ]);

        $role = Role::where('code', $roleCode)->first();

        if ($role) {
            $user->roles()->attach($role->id, ['is_primary' => true]);
        }

        return $user->fresh();
    };

    $this->driver = ($this->buatUser)('requester', 'requester');
    $this->ga = ($this->buatUser)('general_affairs', 'general_affairs');
    $this->superadmin = ($this->buatUser)('superadmin', 'superadmin');

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved' => true,
        'is_active' => true,
        'unit_id' => $this->unit->id,
    ]);
});

it('menyembunyikan dan menolak kendaraan yang sedang dipakai SPD aktif untuk BBM operasional', function () {
    $spdVehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved' => true,
        'is_active' => true,
        'unit_id' => $this->unit->id,
        'consumerial_code' => 'B 9999 SPD',
    ]);

    SuratSpd::factory()->create([
        'user_id' => $this->driver->id,
        'vehicle_id' => $spdVehicle->id,
        'unit_id' => $this->unit->id,
        'status' => 'approved',
        'tanggal_berangkat' => now()->subDay()->toDateString(),
        'tanggal_kembali' => now()->addDay()->toDateString(),
    ]);

    $freeVehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved' => true,
        'is_active' => true,
        'unit_id' => $this->unit->id,
        'consumerial_code' => 'B 1111 BEBAS',
    ]);

    // Halaman form BBM tidak menampilkan kendaraan yang sedang dipakai SPD.
    $this->actingAs($this->driver)
        ->get(route('requester-index'))
        ->assertOk()
        ->assertDontSee('B 9999 SPD', false)
        ->assertSee('B 1111 BEBAS', false);

    // Server tetap menolak walau request dikirim manual.
    $this->actingAs($this->driver)
        ->post(route('requester-create'), [
            'vehicle_id' => $spdVehicle->id,
            'gasoline_type' => 'Pertalite',
            'bill_payment' => 500000,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Permohonan::where('consumerial_tools_id', $spdVehicle->id)->count())->toBe(0);

    // Kendaraan bebas tetap bisa diajukan.
    $this->actingAs($this->driver)
        ->post(route('requester-create'), [
            'vehicle_id' => $freeVehicle->id,
            'gasoline_type' => 'Pertalite',
            'bill_payment' => 500000,
        ])
        ->assertRedirect();

    expect(Permohonan::where('consumerial_tools_id', $freeVehicle->id)->count())->toBe(1);
});

it('menampilkan surat SPD yang ditugaskan ke driver lewat Master Pekerja', function () {
    $employee = EmployeeDefault::create([
        'employee_name' => $this->driver->nama_lengkap,
        'position' => 'Driver',
        'is_active' => true,
        'user_id' => $this->driver->id,
        'unit_id' => $this->unit->id,
    ]);

    $surat = SuratSpd::factory()->create([
        // user_id sengaja NULL: relasi ke driver hanya lewat Master Pekerja.
        'user_id' => null,
        'employee_default_id' => $employee->id,
        'vehicle_id' => $this->vehicle->id,
        'unit_id' => $this->unit->id,
        'pengemudi_nama' => $employee->employee_name,
        'status' => 'approved',
        'tanggal_berangkat' => now()->subDay()->toDateString(),
        'tanggal_kembali' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($this->driver)
        ->get(route('requester.surat.index'))
        ->assertOk()
        ->assertSee($surat->nomor_surat, false);

    $this->actingAs($this->driver)
        ->get(route('requester.spd.berjalan'))
        ->assertOk()
        ->assertSee($surat->nomor_surat, false);

    $this->actingAs($this->driver)
        ->get(route('requester.surat.show', $surat))
        ->assertOk();
});

it('mengambil nama dan jabatan pemberi tugas dari Master Pekerja agar sama dengan template surat', function () {
    $pejabat = EmployeeDefault::create([
        'employee_name' => 'Genny Kusumaningrum',
        'position' => 'Manager',
        'is_active' => true,
        'unit_id' => $this->unit->id,
    ]);

    $this->actingAs($this->superadmin)
        ->post(route('surat.superadmin.store-shortcut'), [
            'pemberi_tugas_level' => 'manager',
            'pemberi_employee_id' => $pejabat->id,
            // Nilai ini sengaja beda (mis. hasil ketik manual) — server wajib
            // memakai data Master Pekerja.
            'pemberi_tugas_nama' => 'Nama Salah Ketik',
            'pemberi_tugas_jabatan' => 'Jabatan Salah',
            'pengemudi_nama' => $this->driver->nama_lengkap,
            'tempat_tujuan' => 'Bandung',
            'melayani_bidang' => 'Operasional',
            'jumlah_pemakai' => 1,
            'vehicle_id' => $this->vehicle->id,
            'tanggal_berangkat' => now()->addDay()->toDateString(),
            'tanggal_kembali' => now()->addDays(3)->toDateString(),
            'type' => 'biasa',
        ])
        ->assertRedirect();

    $surat = SuratSpd::latest('id')->firstOrFail();

    expect($surat->pemberi_tugas_nama)->toBe('Genny Kusumaningrum')
        ->and($surat->pemberi_tugas_jabatan)->toBe('Manager');
});

it('validasi GA memakai nama dan jabatan pemberi tugas dari Master Pekerja', function () {
    $pejabat = EmployeeDefault::create([
        'employee_name' => 'Genny Kusumaningrum',
        'position' => 'Manager',
        'is_active' => true,
        'unit_id' => $this->unit->id,
    ]);

    $surat = SuratSpd::factory()->create([
        'user_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'unit_id' => $this->unit->id,
        'status' => 'pending',
        'pemberi_tugas_nama' => null,
        'pemberi_tugas_jabatan' => null,
        'tanggal_berangkat' => now()->addDay()->toDateString(),
        'tanggal_kembali' => now()->addDays(3)->toDateString(),
    ]);

    $this->actingAs($this->ga)
        ->post(route('surat.superadmin.approve', $surat), [
            'pemberi_employee_id' => $pejabat->id,
            'pemberi_tugas_nama' => 'Ketik Manual',
            'pemberi_tugas_jabatan' => 'Jabatan Manual',
        ])
        ->assertRedirect();

    $surat->refresh();

    expect($surat->status)->toBe('approved')
        ->and($surat->pemberi_tugas_nama)->toBe('Genny Kusumaningrum')
        ->and($surat->pemberi_tugas_jabatan)->toBe('Manager')
        ->and($surat->lpj_deadline->toDateString())
            ->toBe(now()->addDays(6)->toDateString());
});

it('surat shortcut yang dibuat untuk driver langsung tampil di POV driver', function () {
    $employee = EmployeeDefault::create([
        'employee_name' => $this->driver->nama_lengkap,
        'position' => 'Driver',
        'is_active' => true,
        'user_id' => $this->driver->id,
        'unit_id' => $this->unit->id,
    ]);

    $this->actingAs($this->superadmin)
        ->post(route('surat.superadmin.store-shortcut'), [
            'pemberi_tugas_level' => 'manager',
            'pemberi_tugas_nama' => 'Manager Uji',
            'pemberi_tugas_jabatan' => 'Manager',
            'pengemudi_employee_id' => $employee->id,
            'pengemudi_nama' => $employee->employee_name,
            'tempat_tujuan' => 'Jakarta',
            'melayani_bidang' => 'Operasional',
            'jumlah_pemakai' => 1,
            'vehicle_id' => $this->vehicle->id,
            'tanggal_berangkat' => now()->toDateString(),
            'tanggal_kembali' => now()->addDays(2)->toDateString(),
            'type' => 'biasa',
        ])
        ->assertRedirect();

    $surat = SuratSpd::latest('id')->firstOrFail();

    expect((int) $surat->user_id)->toBe($this->driver->id)
        ->and((int) $surat->employee_default_id)->toBe($employee->id);

    // Driver bisa melihat surat dan membuat LPJ-nya.
    $this->actingAs($this->driver)
        ->get(route('requester.surat.index'))
        ->assertOk()
        ->assertSee($surat->nomor_surat, false);

    $this->actingAs($this->driver)
        ->get(route('requester.spd.berjalan'))
        ->assertOk()
        ->assertSee($surat->nomor_surat, false);
});

it('perpanjangan disetujui tanpa nominal dari driver dan Admin GA mencatat dana perpanjangan', function () {
    $original = SuratSpd::factory()->create([
        'user_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'unit_id' => $this->unit->id,
        'status' => 'approved',
        'tanggal_berangkat' => now()->toDateString(),
        'tanggal_kembali' => now()->addDays(2)->toDateString(),
        'lpj_deadline' => now()->addDays(5)->endOfDay(),
        'total_budget' => 1500000,
    ]);

    $extensionSurat = $original->createExtension(
        now()->addDays(4)->toDateString(),
        'Pekerjaan di lokasi belum selesai',
        false
    );

    $record = $extensionSurat->extensionRecord;

    // Driver tidak meminta nominal: budget tambahan awal = 0.
    expect((float) $record->budget_tambahan)->toBe(0.0);

    $this->actingAs($this->ga)
        ->post(route('perpanjangan.superadmin.approve', $record), [
            'catatan' => 'Disetujui, dana menyusul',
            'dana_tanggal' => now()->toDateString(),
            'dana_nominal' => 750000,
            'dana_bentuk' => 'Transfer',
            'dana_bank_reference' => 'TRF/2026/11/001',
            'dana_rek_tujuan' => 'BCA 1234567890',
            'dana_catatan' => 'Tambahan biaya akomodasi',
        ])
        ->assertRedirect();

    $record->refresh();
    $extensionSurat->refresh();

    $row = SuratSpdBudget::where('surat_spd_id', $extensionSurat->id)->first();

    expect($record->status)->toBe('approved')
        ->and((float) $record->budget_tambahan)->toBe(750000.0)
        ->and($row)->not->toBeNull()
        ->and((float) $row->nominal)->toBe(750000.0)
        ->and($row->sumber)->toBe(SuratSpdBudget::SUMBER_PERPANJANGAN)
        ->and($row->surat_spd_extension_id)->toBe($record->id)
        ->and($row->tanggal->toDateString())->toBe(now()->toDateString())
        ->and((float) $extensionSurat->total_budget)->toBe(750000.0)
        // Budget surat asli tidak ikut berubah otomatis.
        ->and((float) $original->fresh()->total_budget)->toBe(1500000.0);

    // Admin GA menambah pemberian dana berikutnya dari halaman detail surat
    // (komponen Pemberian Dana yang sama, dengan tanggal).
    $this->actingAs($this->ga)
        ->post(route('surat.superadmin.pencairan.store', $extensionSurat), [
            'pencairans' => [[
                'tanggal' => now()->addDay()->toDateString(),
                'nominal' => 250000,
                'bentuk_pencairan' => 'Tunai',
                'catatan' => 'Tambahan tunai perpanjangan',
            ]],
        ])
        ->assertRedirect();

    $record->refresh();
    $extensionSurat->refresh();

    expect(SuratSpdBudget::where('surat_spd_id', $extensionSurat->id)->count())->toBe(2)
        ->and((float) $record->budget_tambahan)->toBe(1000000.0)
        ->and((float) $extensionSurat->total_budget)->toBe(1000000.0);

    // Tabel Pemberian Dana menandai baris ini sebagai perpanjangan dan
    // menyediakan form tambahan untuk dana berikutnya.
    $this->actingAs($this->ga)
        ->get(route('surat.superadmin.show', $extensionSurat))
        ->assertOk()
        ->assertSee('Tambah Pemberian Dana', false)
        ->assertSee('Perpanjangan', false);
});
