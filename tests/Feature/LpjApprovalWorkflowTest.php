<?php

use App\Models\LpjPenggantian;
use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->driver = User::factory()->create(['role' => 'requester']);
    $this->driver->roles()->attach(Role::where('code', 'requester')->first()->id, ['is_primary' => true]);

    $this->pengguna = User::factory()->create(['role' => 'requester']);
    $this->pengguna->roles()->attach(Role::where('code', 'pengguna_spd')->first()->id, ['is_primary' => true]);

    $this->ga = User::factory()->create(['role' => 'general_affairs']);
    $this->ga->roles()->attach(Role::where('code', 'general_affairs')->first()->id, ['is_primary' => true]);

    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->manager->roles()->attach(Role::where('code', 'manager')->first()->id, ['is_primary' => true]);

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $this->surat = SuratSpd::factory()->create([
        'user_id'    => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'status'     => 'approved',
    ]);

    $this->lpj = LpjPenggantian::create([
        'surat_spd_id' => $this->surat->id,
        'user_id'      => $this->driver->id,
        'vehicle_id'   => $this->vehicle->id,
        'nomor_polisi' => 'D 1234 TST',
        'nama'         => 'Driver Uji',
        'jabatan'      => 'Driver',
        'uraian'       => 'Uji workflow approval',
        'status'       => 'pending',
    ]);
});

it('setelah validasi Admin GA, LPJ masuk antrean Manager dan belum final', function () {
    // Tahap 1 — User (penumpang).
    $this->actingAs($this->pengguna)
        ->post(route('lpj.pengguna.approve', $this->lpj), ['pernyataan' => '1'])
        ->assertRedirect(route('lpj.pengguna.show', $this->lpj));

    // Tahap 2 — Divisi GA.
    $this->actingAs($this->ga)
        ->post(route('lpj.superadmin.approve-ga', $this->lpj))
        ->assertRedirect();

    $this->lpj->refresh();
    expect($this->lpj->approved_by_divisi_ga)->toBe($this->ga->id);
    expect($this->lpj->approved_by_management)->toBeNull();
    // BUG-LPJ-003: status TIDAK boleh langsung approved di tahap GA.
    expect($this->lpj->status)->toBe('pending');
    expect($this->surat->fresh()->status)->toBe('approved');

    // LPJ muncul di antrean validasi final Manager.
    $this->actingAs($this->manager)
        ->get(route('lpj.management.index'))
        ->assertStatus(200)
        ->assertSee($this->surat->nomor_surat, false);

    // Halaman detail dari konteks Manager bisa dibuka dan menampilkan aksi final.
    $this->actingAs($this->manager)
        ->get(route('lpj.management.show', $this->lpj))
        ->assertStatus(200)
        ->assertSee('Validasi Final sebagai Management', false);

    // Tahap 3 — Management = final approval.
    $this->actingAs($this->manager)
        ->post(route('lpj.management.approve', $this->lpj))
        ->assertRedirect();

    $this->lpj->refresh();
    expect($this->lpj->approved_by_management)->toBe($this->manager->id);
    expect($this->lpj->status)->toBe('approved');
    expect($this->surat->fresh()->status)->toBe('completed');
});

it('Admin GA tidak bisa memakai approve-all untuk melompati Manager', function () {
    $this->actingAs($this->ga)
        ->post(route('lpj.superadmin.approve', $this->lpj))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->lpj->refresh();
    expect($this->lpj->status)->toBe('pending');
    expect($this->lpj->approved_by_divisi_ga)->toBeNull();
    expect($this->lpj->approved_by_management)->toBeNull();
});

it('LPJ lama yang sudah approved tidak mundur dan tidak masuk antrean Manager', function () {
    $legacy = LpjPenggantian::create([
        'surat_spd_id'         => $this->surat->id,
        'user_id'              => $this->driver->id,
        'vehicle_id'           => $this->vehicle->id,
        'nomor_polisi'         => 'D 5678 LMN',
        'nama'                 => 'Driver Lama',
        'jabatan'              => 'Driver',
        'uraian'               => 'LPJ lama sebelum alur 3 tahap',
        'status'               => 'approved',
        'approved_by_pengguna' => $this->pengguna->id,
        'approved_pengguna_at' => now(),
        'approved_by_divisi_ga' => $this->ga->id,
        'approved_divisi_ga_at' => now(),
    ]);

    $this->actingAs($this->manager)
        ->get(route('lpj.management.index'))
        ->assertStatus(200);

    $legacy->refresh();
    expect($legacy->status)->toBe('approved');
    expect($legacy->approved_by_management)->toBeNull();

    // Tetap tampil sebagai dokumen final (tab disetujui), bukan antrean pending.
    $pending = LpjPenggantian::pending()
        ->whereNotNull('approved_by_pengguna')
        ->whereNotNull('approved_by_divisi_ga')
        ->whereNull('approved_by_management')
        ->whereKey($legacy->id)
        ->exists();

    expect($pending)->toBeFalse();
});
