<?php

use App\Models\Module;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Services\PostLoginRedirect;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RbacSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->seed(ModuleSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->superadmin->roles()->attach(
        Role::where('code', 'superadmin')->first()->id,
        ['is_primary' => true]
    );
});

/* =========================================================
 * Titik masuk setelah login
 * =======================================================*/

it('mengarahkan superadmin ke All Modules setelah login', function () {
    $redirect = app(PostLoginRedirect::class);

    expect($redirect->routeFor($this->superadmin->fresh()))
        ->toBe(route('modules.index'));
});

it('tetap memakai alur lama bila seeder modul belum dijalankan', function () {
    // Simulasi deploy lama: tabel modules kosong.
    DB::table('module_permissions')->delete();
    DB::table('modules')->delete();

    $redirect = app(PostLoginRedirect::class);

    expect($redirect->routeFor($this->superadmin->fresh()))
        ->toBe(route('superadmin-dashboard'));
});

/* =========================================================
 * Halaman All Modules
 * =======================================================*/

it('menampilkan seluruh modul yang bisa dibuka superadmin', function () {
    $this->actingAs($this->superadmin)
        ->get(route('modules.index'))
        ->assertOk()
        ->assertSee('All Modules')
        ->assertSee('BBM Operasional')
        ->assertSee('Perjalanan Dinas (SPD & LPJ)')
        ->assertSee('Kendaraan & Alat')
        ->assertSee('Lunch Management')
        ->assertSee('RBAC & Hak Akses')
        ->assertSee('Pengaturan & Data Master')
        // Deskripsi siklus pada kartu modul sudah dihapus — kartu hanya
        // menampilkan nama modul dan tombol Buka Modul.
        ->assertDontSee('Siklus BBM: permohonan')
        ->assertDontSee('Siklus lunch: master pekerja')
        // Halaman pemilih modul tidak menampilkan sidebar modul.
        ->assertDontSee('Modul Aktif');
});

it('menyaring kartu modul berdasarkan permission role', function () {
    $dispatcher = User::factory()->create([
        'role' => 'dispatcher',
        'unit_id' => Unit::where('code', 'PATUHA')->first()->id,
    ]);
    $dispatcher->roles()->attach(Role::where('code', 'dispatcher')->first()->id, ['is_primary' => true]);

    $this->actingAs($dispatcher->fresh())
        ->get(route('modules.index'))
        ->assertOk()
        ->assertSee('Kendaraan & Alat')
        ->assertDontSee('Lunch Management')
        ->assertDontSee('RBAC & Hak Akses');
});

it('menyembunyikan modul yang dimatikan untuk unit user', function () {
    $patuha = Unit::where('code', 'PATUHA')->first();
    $vehicle = Module::where('code', 'vehicle')->first();

    DB::table('unit_modules')
        ->where('unit_id', $patuha->id)
        ->where('module_id', $vehicle->id)
        ->update(['is_enabled' => false]);

    $dispatcher = User::factory()->create([
        'role' => 'dispatcher',
        'unit_id' => $patuha->id,
    ]);
    $dispatcher->roles()->attach(Role::where('code', 'dispatcher')->first()->id, ['is_primary' => true]);

    $this->actingAs($dispatcher->fresh())
        ->get(route('modules.index'))
        ->assertOk()
        ->assertSee('Belum ada modul yang bisa dibuka')
        ->assertDontSee('Kendaraan & Alat');
});

/* =========================================================
 * Membuka modul & isolasi route
 * =======================================================*/

it('membuka modul BBM lalu membatasi sidebar ke menu BBM saja', function () {
    $this->actingAs($this->superadmin)
        ->post(route('modules.activate', 'bbm'))
        ->assertRedirect(route('modules.dashboard.bbm'))
        ->assertSessionHas('module_context.active', 'bbm');

    $this->actingAs($this->superadmin)
        ->get(route('modules.dashboard.bbm'))
        ->assertOk()
        ->assertSee('Modul aktif')
        ->assertSee('Dashboard BBM Operasional')
        ->assertSee('Permohonan Masuk')
        ->assertSee('Cetak Laporan')
        ->assertDontSee('SPD Management')
        ->assertDontSee('Lunch Management')
        ->assertDontSee('Role & Permission');
});

it('menolak route modul lain selama modul aktif dan mengembalikan ke All Modules', function () {
    $this->actingAs($this->superadmin)
        ->post(route('modules.activate', 'bbm'));

    $this->actingAs($this->superadmin)
        ->get(route('lunch.dashboard'))
        ->assertRedirect(route('modules.index'))
        ->assertSessionHas('warning');

    // Halaman All Modules melepas konteks modul sebelumnya.
    $this->actingAs($this->superadmin)
        ->get(route('modules.index'))
        ->assertOk();

    $this->actingAs($this->superadmin)
        ->get(route('lunch.dashboard'))
        ->assertOk();
});

it('mengizinkan route lintas modul seperti profil dari dalam modul', function () {
    $this->actingAs($this->superadmin)
        ->post(route('modules.activate', 'bbm'));

    $this->actingAs($this->superadmin)
        ->get(route('profile'))
        ->assertOk();
});

it('menolak modul yang tidak boleh dibuka user', function () {
    $requester = User::factory()->create([
        'role' => 'requester',
        'unit_id' => Unit::where('code', 'PATUHA')->first()->id,
    ]);
    $requester->roles()->attach(Role::where('code', 'requester')->first()->id, ['is_primary' => true]);

    $this->actingAs($requester->fresh())
        ->post(route('modules.activate', 'lunch'))
        ->assertForbidden();
});

it('membuka modul lewat deep link tanpa harus lewat All Modules', function () {
    $this->actingAs($this->superadmin)
        ->get(route('surat.superadmin.index'))
        ->assertOk()
        ->assertSessionHas('module_context.active', 'spd');

    // Setelah konteks SPD aktif, route modul lain ditolak.
    $this->actingAs($this->superadmin)
        ->get(route('vehicles'))
        ->assertRedirect(route('modules.index'));
});
