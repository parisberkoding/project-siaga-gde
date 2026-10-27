<?php

use App\Models\LpjPenggantian;
use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\User;
use App\Services\PostLoginRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\UnitSeeder::class);
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->buatUser = function (string $roleCode, string $legacyRole): User {
        $user = User::factory()->create(['role' => $legacyRole]);
        $role = Role::where('code', $roleCode)->first();

        if ($role) {
            $user->roles()->attach($role->id, ['is_primary' => true]);
        }

        return $user->fresh();
    };
});

it('menyembunyikan menu Validasi Perjalanan dan memblokir aksesnya untuk driver', function () {
    $driver = ($this->buatUser)('requester', 'requester');

    // Sidebar driver tidak lagi menampilkan menu validasi pengguna.
    $this->actingAs($driver)
        ->get(route('requester.spd.berjalan'))
        ->assertStatus(200)
        ->assertDontSee('Validasi Perjalanan (User)', false);

    // Route validasi pengguna ditolak (403) untuk driver.
    $this->actingAs($driver)
        ->get(route('lpj.pengguna.index'))
        ->assertStatus(403);

    expect($driver->hasPermission('spd.validate_trip'))->toBeFalse();
    // Hak driver yang lain tetap utuh.
    expect($driver->hasPermission('spd.create'))->toBeTrue()
        ->and($driver->hasPermission('lpj.create'))->toBeTrue();
});

it('menampilkan menu dan mengizinkan validasi perjalanan untuk role pegawai', function () {
    $pegawai = ($this->buatUser)('pegawai', 'pegawai');

    $this->actingAs($pegawai)
        ->get(route('lpj.pengguna.index'))
        ->assertStatus(200)
        ->assertSee('Validasi Perjalanan (User)', false);

    expect($pegawai->hasPermission('spd.validate_trip'))->toBeTrue();
});

it('role pegawai tidak membawa hak tulis (CRUD) SPD & LPJ', function () {
    $pegawai = ($this->buatUser)('pegawai', 'pegawai');

    foreach (['spd.create', 'spd.update', 'spd.delete', 'spd.manage', 'spd.approve', 'lpj.create', 'lpj.update', 'lpj.delete', 'lpj.validate'] as $permission) {
        expect($pegawai->hasPermission($permission))->toBeFalse("pegawai seharusnya TIDAK punya {$permission}");
    }

    // Yang boleh: melihat + memvalidasi perjalanan.
    foreach (['dashboard.view', 'spd.view', 'lpj.view', 'spd.validate_trip'] as $permission) {
        expect($pegawai->hasPermission($permission))->toBeTrue("pegawai seharusnya punya {$permission}");
    }
});

it('mengarahkan pegawai ke daftar validasi perjalanan setelah login', function () {
    $pegawai = ($this->buatUser)('pegawai', 'pegawai');
    $redirect = app(PostLoginRedirect::class);

    expect($redirect->routeFor($pegawai))->toBe(route('lpj.pengguna.index'));

    // Driver diarahkan ke dashboard driver miliknya sendiri (bukan ke
    // dashboard analitik unit milik superadmin/GA).
    $driver = ($this->buatUser)('requester', 'requester');
    expect($redirect->routeFor($driver))->toBe(route('driver.dashboard'));
});

it('menyiapkan akun demo pegawai lewat PegawaiDemoSeeder', function () {
    $this->seed(\Database\Seeders\PegawaiDemoSeeder::class);

    $user = User::whereRaw('LOWER(username) = ?', ['faris'])->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe('pegawai')
        ->and($user->roles()->pluck('code')->all())->toBe(['pegawai'])
        ->and($user->hasPermission('spd.validate_trip'))->toBeTrue()
        ->and($user->hasPermission('spd.create'))->toBeFalse();

    // Akun demo harus bisa login lokal (bukan jalur SSO).
    expect($user->auth_provider)->toBe('local')
        ->and($user->is_active)->toBeTrue();

    // Dijalankan dua kali tidak boleh menggandakan akun.
    $this->seed(\Database\Seeders\PegawaiDemoSeeder::class);
    expect(User::whereRaw('LOWER(username) = ?', ['faris'])->count())->toBe(1);
});

it('membuat akun demo pegawai tanpa scope unit agar daftar validasi terisi', function () {
    $this->seed(\Database\Seeders\PegawaiDemoSeeder::class);

    $pegawai = User::whereRaw('LOWER(username) = ?', ['faris'])->first();

    // Tanpa unit → scope unit tidak diterapkan, sehingga LPJ demo (yang
    // suratnya tidak ber-unit) tetap muncul di daftar validasi.
    expect($pegawai->unit_id)->toBeNull()
        ->and($pegawai->default_site_id)->not->toBeNull();

    $surat = SuratSpd::factory()->create(['unit_id' => null, 'status' => 'approved']);
    $lpj = LpjPenggantian::create([
        'surat_spd_id' => $surat->id,
        'user_id'      => $pegawai->id,
        'vehicle_id'   => $surat->vehicle_id,
        'nomor_polisi' => 'D 7777 PEG',
        'nama'         => 'Driver Uji',
        'jabatan'      => 'Pengemudi',
        'uraian'       => 'Uji daftar validasi pegawai',
        'status'       => 'pending',
    ]);

    $this->actingAs($pegawai)
        ->get(route('lpj.pengguna.index'))
        ->assertStatus(200)
        ->assertSee($surat->nomor_surat, false);
});
