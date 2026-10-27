<?php

use App\Models\Permohonan;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleContext;
use App\Services\PostLoginRedirect;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Dashboard Driver terpisah dari dashboard analitik milik superadmin/GA.
 *
 * Yang dijaga di sini:
 *  - driver mendarat di dashboard-nya sendiri setelah login,
 *  - isinya hanya data milik driver yang login (tidak ada agregat unit),
 *  - driver tidak bisa membuka dashboard analitik BBM (dialihkan),
 *  - role lain tidak bisa membuka dashboard driver.
 */
beforeEach(function () {
    $this->seed(UnitSeeder::class);
    $this->seed(RbacSeeder::class);
    $this->seed(ModuleSeeder::class);

    $this->buatUser = function (string $roleCode, string $legacyRole): User {
        $user = User::factory()->create(['role' => $legacyRole]);
        $role = Role::where('code', $roleCode)->first();

        $user->roles()->attach($role->id, ['is_primary' => true]);

        return $user->fresh();
    };
});

it('mendaratkan driver di dashboard driver setelah login', function () {
    $driver = ($this->buatUser)('requester', 'requester');

    expect(app(PostLoginRedirect::class)->routeFor($driver))
        ->toBe(route('driver.dashboard'));
});

it('menampilkan hanya permohonan BBM milik driver yang login', function () {
    $driver = ($this->buatUser)('requester', 'requester');
    $driverLain = ($this->buatUser)('requester', 'requester');

    Permohonan::factory()->create([
        'requester_id' => $driver->id,
        'request_number' => 'REQ-MILIK-DRIVER',
        'status' => 'pending',
    ]);

    Permohonan::factory()->create([
        'requester_id' => $driverLain->id,
        'request_number' => 'REQ-MILIK-DRIVER-LAIN',
        'status' => 'pending',
    ]);

    $this->actingAs($driver)
        ->get(route('driver.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Driver')
        ->assertSee('REQ-MILIK-DRIVER')
        ->assertDontSee('REQ-MILIK-DRIVER-LAIN');
});

it('mengalihkan driver yang membuka dashboard analitik BBM ke dashboard driver', function () {
    $driver = ($this->buatUser)('requester', 'requester');
    $moduleContext = app(ModuleContext::class);

    // Deep link ke dashboard modul mengaktifkan modul tersebut lebih dulu,
    // jadi konteksnya dilepas di antara request agar tidak saling mengunci.
    foreach (['modules.dashboard.bbm', 'modules.dashboard.spd', 'modules.dashboard.vehicle'] as $routeName) {
        $moduleContext->forget();

        $this->actingAs($driver)
            ->get(route($routeName))
            ->assertRedirect(route('driver.dashboard'));
    }

    $this->actingAs($driver)
        ->get(route('superadmin.dashboard.bisnis'))
        ->assertForbidden();
});

it('tetap mendaratkan driver di dashboard driver saat membuka modul dari All Modules', function () {
    $driver = ($this->buatUser)('requester', 'requester');

    $this->actingAs($driver)
        ->post(route('modules.activate', 'bbm'))
        ->assertRedirect(route('driver.dashboard'))
        ->assertSessionHas('module_context.active', 'bbm');

    $this->actingAs($driver)
        ->get(route('driver.dashboard'))
        ->assertOk();
});

it('menolak dashboard driver untuk role yang bukan driver', function () {
    $pegawai = ($this->buatUser)('pegawai', 'pegawai');

    $this->actingAs($pegawai)
        ->get(route('driver.dashboard'))
        ->assertForbidden();
});

it('menampilkan menu Dashboard driver di sidebar', function () {
    $driver = ($this->buatUser)('requester', 'requester');

    $this->actingAs($driver)
        ->get(route('driver.dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('driver.dashboard').'"', false);
});
