<?php

use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Database\Seeders\RbacSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RbacSeeder::class);
    $this->seed(UnitSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->superadmin->roles()->attach(
        Role::where('code', 'superadmin')->first()->id,
        ['is_primary' => true]
    );
});

it('tidak melempar missing parameter saat slug surat kosong dan tetap bisa dibuka lewat id', function () {
    $surat = SuratSpd::factory()->create();

    // Simulasi baris hasil seeder lama / import DB::table tanpa event creating.
    DB::table('surat_spds')->where('id', $surat->id)->update(['slug' => null]);
    $surat->refresh();

    expect($surat->slug)->toBeNull();

    $url = route('surat.superadmin.show', $surat);

    expect($url)->toContain('/surat/'.$surat->id);

    $this->actingAs($this->superadmin)
        ->get($url)
        ->assertOk();
});

it('memastikan seluruh route mengarah ke controller method yang benar-benar ada', function () {
    $invalid = [];

    foreach (Route::getRoutes() as $route) {
        $action = $route->getActionName();

        if (! str_contains($action, '@')) {
            continue;
        }

        [$class, $method] = explode('@', $action);

        if (! class_exists($class) || ! method_exists($class, $method)) {
            $invalid[] = $route->uri().' -> '.$action;
        }
    }

    expect($invalid)->toBe([]);
});

it('tidak memiliki nama route duplikat', function () {
    $names = collect(Route::getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter()
        ->values();

    expect($names->count())->toBe($names->unique()->count());
});

it('memetakan setiap route ke satu modul atau daftar route lintas modul', function () {
    $context = app(\App\Services\ModuleContext::class);

    $unmapped = collect(Route::getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter()
        ->reject(fn (string $name) => $context->isSharedRoute($name)
            || $context->moduleForRoute($name) !== null)
        ->values();

    expect($unmapped->all())->toBe([]);
});

it('menautkan riwayat pengecekan rutin ke halaman frekuensi yang benar', function () {
    $dispatcher = User::factory()->create(['role' => 'dispatcher']);
    $dispatcher->roles()->attach(
        Role::where('code', 'dispatcher')->first()->id,
        ['is_primary' => true]
    );

    $driver = User::factory()->create(['role' => 'requester']);
    $vehicle = VehicleAndToolsConsumers::factory()->create();

    $checkingId = DB::table('vehicle_checkings')->insertGetId([
        'checking_code' => 'CHK-TEST-2026-10',
        'vehicle_id' => $vehicle->id,
        'nopol' => $vehicle->consumerial_code,
        'revision' => 0,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'generated_by' => $dispatcher->id,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('vehicle_checking_details')->insert([
        'checking_detail_code' => 'CHK-TEST-2026-10-001',
        'vehicle_checking_id' => $checkingId,
        'type' => 'routine',
        'sub_type' => 'weekly',
        'dispatcher_id' => $dispatcher->id,
        'current_driver_id' => $driver->id,
        'checked_at' => now(),
        'driver_acknowledged' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($dispatcher->fresh())
        ->get(route('dispatcher.history'))
        ->assertOk()
        ->assertSee(route('dispatcher.weekly-check.show', 'CHK-TEST-2026-10-001'), false);
});
