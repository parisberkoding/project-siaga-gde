<?php

use App\Models\EmployeeDefault;
use App\Models\LpjPenggantian;
use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->driver = User::factory()->create(['role' => 'requester']);
    $roleReq = Role::where('code', 'requester')->first();
    $this->driver->roles()->attach($roleReq->id, ['is_primary' => true]);

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);
});

it('lookup pemakai mengembalikan nama + user_id dari Master Pekerja', function () {
    // Pekerja dengan akun.
    $akun = User::factory()->create(['nama_lengkap' => 'Budi Santoso']);
    EmployeeDefault::create([
        'user_id'       => $akun->id,
        'employee_name' => 'Budi Santoso',
        'position'      => 'Operator',
        'is_active'     => true,
    ]);

    // Pekerja tanpa akun (user_id null) — tetap boleh dipilih.
    EmployeeDefault::create([
        'employee_name' => 'Budi Tanpa Akun',
        'position'      => 'Teknisi',
        'is_active'     => true,
    ]);

    $res = $this->actingAs($this->driver)
        ->getJson(route('spd.passenger.lookup', ['q' => 'Budi']))
        ->assertStatus(200)
        ->json('results');

    expect($res)->toHaveCount(2);
    $denganAkun = collect($res)->firstWhere('name', 'Budi Santoso');
    expect($denganAkun['user_id'])->toBe($akun->id);

    $tanpaAkun = collect($res)->firstWhere('name', 'Budi Tanpa Akun');
    expect($tanpaAkun['user_id'])->toBeNull();
});

it('lookup pemakai mengembalikan kosong bila query kurang dari 2 huruf', function () {
    $this->actingAs($this->driver)
        ->getJson(route('spd.passenger.lookup', ['q' => 'B']))
        ->assertStatus(200)
        ->assertJson(['results' => []]);
});

it('form buat surat merender input autocomplete pemakai', function () {
    $html = $this->actingAs($this->driver)
        ->get(route('requester.surat.create'))
        ->assertStatus(200)
        ->getContent();

    expect($html)->toContain('pemakai-input')
        ->toContain('name="user_id_1"')
        ->toContain('pemakai-suggestions-global')
        ->toContain('position:fixed');

    // Tidak boleh ada <script> bersarang.
    expect(substr_count($html, '<script'))->toBe(substr_count($html, '</script>'));
});

it('form shortcut merender input autocomplete pemakai', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    $admin->roles()->attach($role->id, ['is_primary' => true]);

    $html = $this->actingAs($admin)
        ->get(route('surat.superadmin.create-shortcut'))
        ->assertStatus(200)
        ->getContent();

    expect($html)->toContain('pemakai-input')
        ->toContain('name="user_id_1"')
        ->toContain('pemakai-suggestions-global');

    expect(substr_count($html, '<script'))->toBe(substr_count($html, '</script>'));
});

it('membuat SPD menyimpan user_id tiap pemakai (nullable)', function () {
    $akun = User::factory()->create(['nama_lengkap' => 'Dewi Rahayu']);
    EmployeeDefault::create([
        'user_id'       => $akun->id,
        'employee_name' => 'Dewi Rahayu',
        'is_active'     => true,
    ]);

    $this->actingAs($this->driver)
        ->post(route('requester.surat.store'), [
            'tempat_tujuan'     => 'Bandung',
            'melayani_bidang'   => 'Operasional',
            'jumlah_pemakai'    => 2,
            'vehicle_id'        => $this->vehicle->id,
            'tanggal_berangkat' => now()->addDay()->format('Y-m-d'),
            'tanggal_kembali'   => now()->addDays(3)->format('Y-m-d'),
            'jenis_spd'         => 'luar_kota',
            'nama_lengkap_1'    => 'Dewi Rahayu',
            'user_id_1'         => $akun->id,
            'nama_lengkap_2'    => 'Tamu Eksternal',
            'user_id_2'         => '',
        ])
        ->assertRedirect();

    $surat = SuratSpd::latest('id')->first();
    $details = $surat->details()->orderBy('urutan')->get();

    expect($details)->toHaveCount(2);
    expect($details[0]->nama_lengkap)->toBe('Dewi Rahayu');
    expect($details[0]->user_id)->toBe($akun->id);
    expect($details[1]->nama_lengkap)->toBe('Tamu Eksternal');
    expect($details[1]->user_id)->toBeNull();
});

it('cukup satu penumpang memvalidasi — penumpang lain tidak perlu lagi', function () {
    // Dua penumpang punya akun & permission validasi.
    $p1 = User::factory()->create(['role' => 'requester']);
    $p2 = User::factory()->create(['role' => 'requester']);
    $rolePengguna = Role::where('code', 'pengguna_spd')->first();
    $p1->roles()->attach($rolePengguna->id, ['is_primary' => true]);
    $p2->roles()->attach($rolePengguna->id, ['is_primary' => true]);

    $surat = SuratSpd::factory()->create([
        'user_id'    => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'status'     => 'approved',
    ]);
    $surat->details()->createMany([
        ['nama_lengkap' => 'Penumpang Satu', 'user_id' => $p1->id, 'urutan' => 1],
        ['nama_lengkap' => 'Penumpang Dua',  'user_id' => $p2->id, 'urutan' => 2],
    ]);

    $lpj = LpjPenggantian::create([
        'surat_spd_id' => $surat->id,
        'user_id'      => $this->driver->id,
        'vehicle_id'   => $this->vehicle->id,
        'nomor_polisi' => 'D 2222 PNP',
        'nama'         => 'Penumpang Satu',
        'jabatan'      => 'Staff',
        'uraian'       => 'Uji satu penumpang',
        'status'       => 'pending',
    ]);

    // Sebelum divalidasi, penumpang kedua melihat tugasnya.
    $this->actingAs($p2)
        ->get(route('lpj.pengguna.index'))
        ->assertStatus(200)
        ->assertSee($surat->nomor_surat, false);

    // Penumpang pertama memvalidasi.
    $this->actingAs($p1)
        ->post(route('lpj.pengguna.approve', $lpj), ['pernyataan' => '1'])
        ->assertRedirect(route('lpj.pengguna.show', $lpj));

    expect($lpj->fresh()->approved_by_pengguna)->toBe($p1->id);

    // Penumpang kedua tidak perlu lagi — LPJ hilang dari daftar tugasnya.
    $this->actingAs($p2)
        ->get(route('lpj.pengguna.index'))
        ->assertStatus(200)
        ->assertDontSee($surat->nomor_surat, false)
        ->assertSee('Tidak ada', false);
});
