<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

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

it('menyediakan dashboard untuk setiap modul dan menjadikannya halaman pendarat modul', function () {
    $expected = [
        'bbm' => ['modules.dashboard.bbm', 'Dashboard BBM Operasional'],
        'spd' => ['modules.dashboard.spd', 'Dashboard SPD & LPJ'],
        'vehicle' => ['modules.dashboard.vehicle', 'Dashboard Kendaraan & Alat'],
        'lunch' => ['lunch.dashboard', 'Dashboard Lunch Management'],
        'ai' => ['modules.dashboard.ai', 'Dashboard AI Assistant'],
        'users' => ['modules.dashboard.users', 'Dashboard Manajemen Pengguna'],
        'rbac' => ['modules.dashboard.rbac', 'Dashboard RBAC & Hak Akses'],
        'settings' => ['modules.dashboard.settings', 'Dashboard Pengaturan & Data Master'],
    ];

    foreach ($expected as $moduleCode => [$routeName, $heading]) {
        expect(Route::has($routeName))->toBeTrue("Route {$routeName} tidak terdaftar");

        $this->actingAs($this->superadmin)
            ->post(route('modules.activate', $moduleCode))
            ->assertRedirect(route($routeName))
            ->assertSessionHas('module_context.active', $moduleCode);

        $this->actingAs($this->superadmin)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee($heading)
            ->assertSee($moduleCode === 'lunch' ? 'Dashboard Lunch Management' : 'Modul Aktif');
    }
});

it('menolak dashboard modul lain saat konteks modul sedang aktif', function () {
    $this->actingAs($this->superadmin)
        ->post(route('modules.activate', 'spd'));

    $this->actingAs($this->superadmin)
        ->get(route('modules.dashboard.vehicle'))
        ->assertRedirect(route('modules.index'))
        ->assertSessionHas('warning');
});

it('menampilkan dashboard BBM dengan judul modul, bukan dashboard bisnis generik', function () {
    $this->actingAs($this->superadmin)
        ->post(route('modules.activate', 'bbm'));

    $this->actingAs($this->superadmin)
        ->get(route('modules.dashboard.bbm'))
        ->assertOk()
        ->assertSee('Dashboard BBM Operasional')
        ->assertDontSee('<li class="breadcrumb-item active">Dashboard Bisnis</li>', false);
});
