<?php

use App\Models\Module;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);
    $this->seed(\Database\Seeders\ModuleSeeder::class);

    // Superadmin uji: unit_id null -> diperlakukan sebagai aktor Pusat
    // (lihat catatan transisi di UnitController::canManageModules).
    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->superadmin->roles()->attach(
        Role::where('code', 'superadmin')->first()->id,
        ['is_primary' => true]
    );
});

/** Helper: baca matrix modul sebuah unit sebagai [code => is_enabled]. */
function matrixFor(Unit $unit): array
{
    $out = [];
    foreach (DB::table('unit_modules')->where('unit_id', $unit->id)->get() as $row) {
        $out[Module::find($row->module_id)->code] = (bool) $row->is_enabled;
    }

    return $out;
}

/* =========================================================
 * Seeder & fondasi
 * =======================================================*/

it('menyediakan 8 modul dengan RBAC & Pengaturan selalu aktif', function () {
    expect(Module::count())->toBe(8);
    expect(Module::where('code', 'rbac')->first()->is_always_on)->toBeTrue();
    expect(Module::where('code', 'settings')->first()->is_always_on)->toBeTrue();
    expect(Module::where('code', 'bbm')->first()->is_gateable)->toBeTrue();
});

it('memetakan seluruh permission ke minimal satu modul', function () {
    $total = DB::table('permissions')->count();
    $mapped = DB::table('module_permissions')->distinct()->count('permission_id');

    expect($mapped)->toBe($total);
});

it('menandai unit PUSAT sebagai central dan unit lain tidak', function () {
    expect(Unit::where('code', 'PUSAT')->first()->is_central)->toBeTrue();
    expect(Unit::where('code', 'PATUHA')->first()->is_central)->toBeFalse();
    expect(Unit::where('code', 'DIENG')->first()->is_central)->toBeFalse();
});

it('meng-grandfather semua modul enabled untuk unit eksisting', function () {
    foreach (['PUSAT', 'PATUHA', 'DIENG'] as $code) {
        $matrix = matrixFor(Unit::where('code', $code)->first());
        expect($matrix)->toHaveCount(8);
        expect(array_filter($matrix))->toHaveCount(8);
    }
});

/* =========================================================
 * Unit baru
 * =======================================================*/

it('memberi unit baru hanya modul default (BBM + SPD) plus always-on', function () {
    // Simulasi submit form sebenarnya untuk unit baru: form sudah mencentang
    // modul default_enabled (BBM + SPD). Modul always-on dirender `disabled`
    // sehingga tidak ikut terkirim, dan tetap dipaksa menyala oleh controller.
    $defaultGateableIds = Module::active()->ordered()->get()
        ->filter(fn (Module $m) => $m->is_gateable && $m->default_enabled)
        ->pluck('id')
        ->all();

    expect($defaultGateableIds)->toHaveCount(2); // bbm + spd

    $this->actingAs($this->superadmin)
        ->post(route('superadmin.units.store'), [
            'code' => 'UNITF',
            'name' => 'Unit F',
            'is_active' => 1,
            'modules_present' => 1,
            'modules' => $defaultGateableIds,
        ])
        ->assertRedirect(route('superadmin.units.index'));

    $matrix = matrixFor(Unit::where('code', 'UNITF')->first());

    expect($matrix['bbm'])->toBeTrue();
    expect($matrix['spd'])->toBeTrue();
    expect($matrix['rbac'])->toBeTrue();
    expect($matrix['settings'])->toBeTrue();
    expect($matrix['vehicle'])->toBeFalse();
    expect($matrix['lunch'])->toBeFalse();
    expect($matrix['ai'])->toBeFalse();
    expect($matrix['users'])->toBeFalse();
});

it('memakai default kalau form tidak mengirim penanda modules_present', function () {
    $this->actingAs($this->superadmin)
        ->post(route('superadmin.units.store'), [
            'code' => 'UNITG',
            'name' => 'Unit G',
            'is_active' => 1,
        ])
        ->assertRedirect();

    $matrix = matrixFor(Unit::where('code', 'UNITG')->first());

    expect($matrix['bbm'])->toBeTrue();
    expect($matrix['spd'])->toBeTrue();
    expect($matrix['vehicle'])->toBeFalse();
});

/* =========================================================
 * Checklist saat create & edit
 * =======================================================*/

it('menyimpan checklist modul yang dicentang saat unit dibuat', function () {
    $vehicle = Module::where('code', 'vehicle')->first();
    $lunch = Module::where('code', 'lunch')->first();

    $this->actingAs($this->superadmin)
        ->post(route('superadmin.units.store'), [
            'code' => 'UNITH',
            'name' => 'Unit H',
            'is_active' => 1,
            'modules_present' => 1,
            'modules' => [$vehicle->id, $lunch->id],
        ])
        ->assertRedirect();

    $matrix = matrixFor(Unit::where('code', 'UNITH')->first());

    expect($matrix['vehicle'])->toBeTrue();
    expect($matrix['lunch'])->toBeTrue();
    // selalu aktif, apa pun checklist-nya
    expect($matrix['rbac'])->toBeTrue();
    expect($matrix['settings'])->toBeTrue();
    // tidak dicentang
    expect($matrix['bbm'])->toBeFalse();
    expect($matrix['spd'])->toBeFalse();
});

it('menyimpan ulang checklist saat unit diedit', function () {
    $unit = Unit::where('code', 'PATUHA')->first();
    expect($unit->hasModuleEnabled('vehicle'))->toBeTrue();

    $this->actingAs($this->superadmin)
        ->put(route('superadmin.units.update', $unit), [
            'name' => $unit->name,
            'is_active' => 1,
            'modules_present' => 1,
            'modules' => [],   // lepas semua centang
        ])
        ->assertRedirect();

    $matrix = matrixFor($unit->fresh());

    expect($matrix['vehicle'])->toBeFalse();
    expect($matrix['bbm'])->toBeFalse();
    expect($matrix['spd'])->toBeFalse();
    // always-on tetap menyala
    expect($matrix['rbac'])->toBeTrue();
    expect($matrix['settings'])->toBeTrue();
});

it('tidak pernah mematikan modul always-on', function () {
    $unit = Unit::where('code', 'PATUHA')->first();

    $this->actingAs($this->superadmin)
        ->put(route('superadmin.units.update', $unit), [
            'name' => $unit->name,
            'is_active' => 1,
            'modules_present' => 1,
            'modules' => [],
        ])
        ->assertRedirect();

    expect($unit->fresh()->hasModuleEnabled('rbac'))->toBeTrue();
    expect($unit->fresh()->hasModuleEnabled('settings'))->toBeTrue();
});

it('mencatat audit log saat matrix berubah', function () {
    $unit = Unit::where('code', 'PATUHA')->first();

    DB::table('module_access_logs')->delete();

    $this->actingAs($this->superadmin)
        ->put(route('superadmin.units.update', $unit), [
            'name' => $unit->name,
            'is_active' => 1,
            'modules_present' => 1,
            'modules' => [],
        ])
        ->assertRedirect();

    $logs = DB::table('module_access_logs')->where('unit_id', $unit->id)->get();

    // 6 modul gateable dimatikan; rbac & settings tidak berubah -> tanpa log
    expect($logs)->toHaveCount(6);
    expect($logs->every(fn ($l) => $l->action === 'disable'))->toBeTrue();
});

/* =========================================================
 * Efek terhadap otorisasi user
 * =======================================================*/

it('mencabut permission modul yang dimatikan untuk unit user', function () {
    $patuha = Unit::where('code', 'PATUHA')->first();
    $vehicle = Module::where('code', 'vehicle')->first();

    $patuha->modules()->updateExistingPivot($vehicle->id, ['is_enabled' => false]);

    $user = User::factory()->create(['role' => 'dispatcher', 'unit_id' => $patuha->id]);
    $user->roles()->attach(Role::where('code', 'dispatcher')->first()->id, ['is_primary' => true]);

    $fresh = User::find($user->id);

    expect($fresh->hasModuleAccess('vehicle'))->toBeFalse();
    expect($fresh->hasPermission('vehicle.view'))->toBeFalse();
    expect($fresh->hasPermission('vehicle.checking'))->toBeFalse();
});

it('tidak mengubah permission user di unit Pusat', function () {
    $patuha = Unit::where('code', 'PATUHA')->first();
    $pusat = Unit::where('code', 'PUSAT')->first();
    $vehicle = Module::where('code', 'vehicle')->first();

    // Matikan vehicle untuk SELURUH unit non-Pusat
    foreach (Unit::where('is_central', false)->get() as $u) {
        $u->modules()->updateExistingPivot($vehicle->id, ['is_enabled' => false]);
    }

    $user = User::factory()->create(['role' => 'dispatcher', 'unit_id' => $pusat->id]);
    $user->roles()->attach(Role::where('code', 'dispatcher')->first()->id, ['is_primary' => true]);

    $fresh = User::find($user->id);

    expect($fresh->unitIsCentral())->toBeTrue();
    expect($fresh->hasModuleAccess('vehicle'))->toBeTrue();
    expect($fresh->hasPermission('vehicle.view'))->toBeTrue();
});

it('tetap memberi akses penuh bagi user yang belum punya unit (transisi)', function () {
    $vehicle = Module::where('code', 'vehicle')->first();
    Unit::where('code', 'PATUHA')->first()
        ->modules()->updateExistingPivot($vehicle->id, ['is_enabled' => false]);

    $user = User::factory()->create(['role' => 'dispatcher', 'unit_id' => null]);
    $user->roles()->attach(Role::where('code', 'dispatcher')->first()->id, ['is_primary' => true]);

    $fresh = User::find($user->id);

    expect($fresh->bypassesModuleGate())->toBeTrue();
    expect($fresh->hasPermission('vehicle.view'))->toBeTrue();
});

it('memfilter sebagian permission saja bila modul lain tetap enabled', function () {
    $patuha = Unit::where('code', 'PATUHA')->first();
    $vehicle = Module::where('code', 'vehicle')->first();

    $patuha->modules()->updateExistingPivot($vehicle->id, ['is_enabled' => false]);

    $user = User::factory()->create(['role' => 'superadmin', 'unit_id' => $patuha->id]);
    $user->roles()->attach(Role::where('code', 'superadmin')->first()->id, ['is_primary' => true]);

    $fresh = User::find($user->id);
    $codes = $fresh->getPermissionCodes();

    // Perhitungan dibuat dinamis (bukan angka mati) supaya penambahan
    // permission baru tidak membuat test ini rapuh.
    //
    // Modul vehicle punya N permission, tetapi sebagian dipakai BERSAMA modul
    // lain — `dashboard.view` misalnya dimiliki 5 modul. Mematikan vehicle
    // hanya menghilangkan permission yang TIDAK dimiliki modul lain.
    // Justru inilah alasan pemetaan modul memakai many-to-many.
    $vehiclePermissionIds = $vehicle->permissions()->pluck('permissions.id')->all();

    $sharedIds = DB::table('module_permissions')
        ->whereIn('permission_id', $vehiclePermissionIds)
        ->select('permission_id')
        ->groupBy('permission_id')
        ->havingRaw('COUNT(*) > 1')
        ->pluck('permission_id')
        ->all();

    $hilangUnik = count($vehiclePermissionIds) - count($sharedIds);
    $totalPermission = DB::table('permissions')->count();

    expect($totalPermission - $hilangUnik)->toBeGreaterThan(0);
    expect($codes)->toHaveCount($totalPermission - $hilangUnik);
    expect($codes)->not->toContain('vehicle.view');
    expect($codes)->not->toContain('vehicle.report');
    expect($codes)->toContain('dashboard.view');   // masih hidup via modul lain
    expect($codes)->toContain('bbm.view');
    expect($codes)->toContain('spd.view');
    expect($codes)->toContain('lunch.view');
});

it('mematikan seluruh modul siklus menyisakan hanya permission always-on', function () {
    $patuha = Unit::where('code', 'PATUHA')->first();

    // Matikan semua modul gateable
    foreach (Module::where('is_gateable', true)->get() as $module) {
        $patuha->modules()->updateExistingPivot($module->id, ['is_enabled' => false]);
    }

    $user = User::factory()->create(['role' => 'superadmin', 'unit_id' => $patuha->id]);
    $user->roles()->attach(Role::where('code', 'superadmin')->first()->id, ['is_primary' => true]);

    $codes = User::find($user->id)->getPermissionCodes();

    // Yang tersisa hanya permission milik modul always-on (RBAC & Pengaturan).
    // Dihitung dinamis agar tidak rapuh saat permission baru ditambahkan.
    $alwaysOnPermissionIds = DB::table('module_permissions')
        ->whereIn('module_id', Module::where('is_always_on', true)->pluck('id'))
        ->pluck('permission_id')
        ->unique();

    expect($codes)->toHaveCount($alwaysOnPermissionIds->count());
    expect($codes)->toContain('rbac.roles');
    expect($codes)->toContain('settings.modules');
    expect($codes)->not->toContain('bbm.view');
    expect($codes)->not->toContain('dashboard.view');
});

/* =========================================================
 * Batas kewenangan
 * =======================================================*/

it('menolak superadmin unit non-Pusat mengubah matrix modul', function () {
    $patuha = Unit::where('code', 'PATUHA')->first();

    // Superadmin yang unitnya Patuha: punya permission, tapi bukan Pusat.
    $unitAdmin = User::factory()->create(['role' => 'superadmin', 'unit_id' => $patuha->id]);
    $unitAdmin->roles()->attach(
        Role::where('code', 'superadmin')->first()->id,
        ['is_primary' => true]
    );

    $before = matrixFor($patuha);

    $this->actingAs(User::find($unitAdmin->id))
        ->put(route('superadmin.units.update', $patuha), [
            'name' => $patuha->name,
            'is_active' => 1,
            'modules_present' => 1,
            'modules' => [],
        ])
        ->assertRedirect();

    // Matrix harus TIDAK berubah
    expect(matrixFor($patuha->fresh()))->toBe($before);
});

it('menolak menghapus unit Pusat', function () {
    $pusat = Unit::where('code', 'PUSAT')->first();

    $this->actingAs($this->superadmin)
        ->post(route('superadmin.units.delete', $pusat))
        ->assertSessionHas('error');

    expect(Unit::find($pusat->id))->not->toBeNull();
});

/* =========================================================
 * Fail-open — mencegah outage saat deploy
 * =======================================================*/

it('tidak mengunci akses bila fitur gate modul belum diinisialisasi', function () {
    // Simulasi deploy yang lupa menjalankan ModuleSeeder: tabel modules kosong.
    // Tanpa fail-open, SEMUA user yang sudah punya unit akan kehilangan
    // seluruh permission-nya — outage total.
    DB::table('module_access_logs')->delete();
    DB::table('unit_modules')->delete();
    DB::table('module_permissions')->delete();
    DB::table('modules')->delete();

    app(\App\Services\ModuleGate::class)->flush();

    $unit = Unit::where('code', 'PATUHA')->first();
    $user = User::factory()->create(['role' => 'dispatcher', 'unit_id' => $unit->id]);
    $user->roles()->attach(Role::where('code', 'dispatcher')->first()->id, ['is_primary' => true]);

    $fresh = User::find($user->id);

    expect($fresh->enabledModuleCodes())->toBeNull();
    expect($fresh->hasPermission('vehicle.view'))->toBeTrue();
});

it('tidak mengunci unit yang belum punya baris matrix', function () {
    $unit = Unit::where('code', 'PATUHA')->first();

    // Unit ada, modul ter-inisialisasi, tapi matrix-nya kosong.
    DB::table('unit_modules')->where('unit_id', $unit->id)->delete();

    $user = User::factory()->create(['role' => 'dispatcher', 'unit_id' => $unit->id]);
    $user->roles()->attach(Role::where('code', 'dispatcher')->first()->id, ['is_primary' => true]);

    $fresh = User::find($user->id);

    expect($fresh->enabledModuleCodes())->toBeNull();
    expect($fresh->hasPermission('vehicle.view'))->toBeTrue();
});

it('mulai menggating begitu unit punya baris matrix', function () {
    $unit = Unit::where('code', 'PATUHA')->first();
    $vehicle = Module::where('code', 'vehicle')->first();

    $unit->modules()->updateExistingPivot($vehicle->id, ['is_enabled' => false]);

    $user = User::factory()->create(['role' => 'dispatcher', 'unit_id' => $unit->id]);
    $user->roles()->attach(Role::where('code', 'dispatcher')->first()->id, ['is_primary' => true]);

    $fresh = User::find($user->id);

    expect($fresh->enabledModuleCodes())->not->toBeNull();
    expect($fresh->hasModuleAccess('vehicle'))->toBeFalse();
    expect($fresh->hasPermission('vehicle.view'))->toBeFalse();
});

/* =========================================================
 * Tampilan
 * =======================================================*/

it('menampilkan halaman edit unit beserta checklist modul', function () {
    $unit = Unit::where('code', 'PATUHA')->first();

    $response = $this->actingAs($this->superadmin)
        ->get(route('superadmin.units.edit', $unit))
        ->assertOk()
        ->assertSee('Modul yang Di-enable untuk Unit Ini');

    // 8 checkbox modul
    expect(substr_count($response->getContent(), 'name="modules[]"'))->toBe(8);
});

it('menyembunyikan checklist dari aktor non-Pusat', function () {
    $patuha = Unit::where('code', 'PATUHA')->first();

    $unitAdmin = User::factory()->create(['role' => 'superadmin', 'unit_id' => $patuha->id]);
    $unitAdmin->roles()->attach(
        Role::where('code', 'superadmin')->first()->id,
        ['is_primary' => true]
    );

    $this->actingAs(User::find($unitAdmin->id))
        ->get(route('superadmin.units.edit', $patuha))
        ->assertOk()
        ->assertSee('Hanya superadmin unit')
        ->assertDontSee('name="modules_present"', false);
});
