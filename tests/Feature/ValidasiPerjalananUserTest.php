<?php

use App\Models\LpjPenggantian;
use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    // User yang berangkat — role pengguna_spd (punya spd.validate_trip).
    $this->penumpang = User::factory()->create(['role' => 'requester']);
    $role = Role::where('code', 'pengguna_spd')->first();
    if ($role) {
        $this->penumpang->roles()->attach($role->id, ['is_primary' => true]);
    }

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $this->surat = SuratSpd::factory()->create([
        'user_id'    => $this->penumpang->id,
        'vehicle_id' => $this->vehicle->id,
        'status'     => 'approved',
    ]);

    $this->lpj = LpjPenggantian::create([
        'surat_spd_id' => $this->surat->id,
        'user_id'      => $this->penumpang->id,
        'vehicle_id'   => $this->vehicle->id,
        'nomor_polisi' => 'D 1234 TST',
        'nama'         => 'Penumpang Test',
        'jabatan'      => 'Staff',
        'uraian'       => 'Perjalanan dinas uji',
        'status'       => 'pending',
    ]);
});

it('halaman validasi perjalanan user tampil dengan pernyataan', function () {
    $this->actingAs($this->penumpang)
        ->get(route('lpj.pengguna.show', $this->lpj))
        ->assertStatus(200)
        ->assertSee('Validasi Perjalanan Dinas', false)
        ->assertSee('Saya mengetahui bahwa perjalanan dinas ini benar adanya', false)
        ->assertSee('Validasi &amp; Tanda Tangan', false);
});

it('user dapat memvalidasi kebenaran perjalanan (tahap 1)', function () {
    $this->actingAs($this->penumpang)
        ->post(route('lpj.pengguna.approve', $this->lpj), ['pernyataan' => '1'])
        ->assertRedirect(route('lpj.pengguna.show', $this->lpj));

    $this->lpj->refresh();
    expect($this->lpj->approved_by_pengguna)->toBe($this->penumpang->id);
    expect($this->lpj->approved_pengguna_at)->not->toBeNull();
});

it('validasi user ditolak jika pernyataan tidak dicentang', function () {
    $this->actingAs($this->penumpang)
        ->post(route('lpj.pengguna.approve', $this->lpj), [])
        ->assertSessionHasErrors('pernyataan');

    expect($this->lpj->fresh()->approved_by_pengguna)->toBeNull();
});

it('setelah divalidasi, halaman menampilkan QR tanda tangan', function () {
    $this->actingAs($this->penumpang)
        ->post(route('lpj.pengguna.approve', $this->lpj), ['pernyataan' => '1']);

    $response = $this->actingAs($this->penumpang)
        ->get(route('lpj.pengguna.show', $this->lpj))
        ->assertStatus(200)
        ->assertSee('QR Tanda Tangan Validasi', false)
        ->assertSee('Sudah divalidasi', false);

    // QR dirender sebagai SVG inline.
    expect($response->getContent())->toContain('<svg');
});

it('sidebar menampilkan menu Validasi Perjalanan untuk user berangkat', function () {
    $this->actingAs($this->penumpang)
        ->get(route('lpj.pengguna.index'))
        ->assertStatus(200)
        ->assertSee('Validasi Perjalanan (User)', false);
});

it('peran ganda (User + GA) tetap validasi 2x terpisah, lalu final oleh Manager', function () {
    // Satu orang punya dua peran sekaligus.
    $dual = User::factory()->create(['role' => 'requester']);
    $rolePengguna = Role::where('code', 'pengguna_spd')->first();
    $roleGa = Role::where('code', 'general_affairs')->first();
    $dual->roles()->attach($rolePengguna->id, ['is_primary' => true]);
    $dual->roles()->attach($roleGa->id, ['is_primary' => false]);

    expect($dual->hasPermission('spd.validate_trip'))->toBeTrue();
    expect($dual->hasPermission('lpj.validate'))->toBeTrue();

    $lpj = LpjPenggantian::create([
        'surat_spd_id' => $this->surat->id,
        'user_id'      => $dual->id,
        'vehicle_id'   => $this->vehicle->id,
        'nomor_polisi' => 'D 9999 DUA',
        'nama'         => 'Peran Ganda',
        'jabatan'      => 'GA',
        'uraian'       => 'Uji peran ganda',
        'status'       => 'pending',
    ]);

    // Tahap 1 — sebagai User (penumpang).
    $this->actingAs($dual)
        ->post(route('lpj.pengguna.approve', $lpj), ['pernyataan' => '1'])
        ->assertRedirect(route('lpj.pengguna.show', $lpj));

    $lpj->refresh();
    expect($lpj->approved_by_pengguna)->toBe($dual->id);
    expect($lpj->approved_by_divisi_ga)->toBeNull();
    expect($lpj->status)->toBe('pending');

    // Tahap 2 — sebagai Divisi GA (aksi terpisah).
    $this->actingAs($dual)
        ->post(route('lpj.superadmin.approve-ga', $lpj))
        ->assertRedirect();

    $lpj->refresh();
    expect($lpj->approved_by_divisi_ga)->toBe($dual->id);
    // BUG-LPJ-003: GA bukan tahap final — masih menunggu Management.
    expect($lpj->status)->toBe('pending');

    // Tahap 3 — validasi final oleh Management.
    $manager = User::factory()->create(['role' => 'manager']);
    $roleManager = Role::where('code', 'manager')->first();
    $manager->roles()->attach($roleManager->id, ['is_primary' => true]);

    $this->actingAs($manager)
        ->post(route('lpj.management.approve', $lpj))
        ->assertRedirect();

    $lpj->refresh();
    expect($lpj->approved_by_management)->toBe($manager->id);
    expect($lpj->status)->toBe('approved');
});

it('Divisi GA tidak strict: boleh validasi tanpa menunggu tahap User', function () {
    $ga = User::factory()->create(['role' => 'general_affairs']);
    $roleGa = Role::where('code', 'general_affairs')->first();
    $ga->roles()->attach($roleGa->id, ['is_primary' => true]);

    $lpj = LpjPenggantian::create([
        'surat_spd_id' => $this->surat->id,
        'user_id'      => $this->penumpang->id,
        'vehicle_id'   => $this->vehicle->id,
        'nomor_polisi' => 'D 1111 GA',
        'nama'         => 'Driver',
        'jabatan'      => 'Driver',
        'uraian'       => 'Uji GA lebih dulu',
        'status'       => 'pending',
    ]);

    $this->actingAs($ga)
        ->post(route('lpj.superadmin.approve-ga', $lpj))
        ->assertRedirect();

    $lpj->refresh();
    expect($lpj->approved_by_divisi_ga)->toBe($ga->id);
    // Belum approved penuh karena tahap User belum.
    expect($lpj->status)->toBe('pending');
});
