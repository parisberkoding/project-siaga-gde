<?php

use App\Models\Feature;
use App\Models\Role;
use App\Models\User;
use App\Services\Sso\SsoUserInfo;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);
    $this->seed(\Database\Seeders\ModuleSeeder::class);
    $this->seed(\Database\Seeders\FeatureSeeder::class);

    $this->developer = User::factory()->create(['role' => 'developers']);
    $this->developer->roles()->attach(Role::where('code', 'developers')->first()->id, ['is_primary' => true]);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->superadmin->roles()->attach(Role::where('code', 'superadmin')->first()->id, ['is_primary' => true]);

    $this->requester = User::factory()->create(['role' => 'requester']);
    $this->requester->roles()->attach(Role::where('code', 'requester')->first()->id, ['is_primary' => true]);
});

/* =========================================================
 * Resolusi tahap
 * =======================================================*/

it('memperlakukan fitur yang tidak terdaftar sebagai production (fail-open)', function () {
    expect(Feature::stageFor('fitur.yang.tidak.ada'))->toBeNull();
    expect(Feature::isProduction('fitur.yang.tidak.ada'))->toBeTrue();
    expect(Feature::isRestricted('fitur.yang.tidak.ada'))->toBeFalse();
});

it('mendaftarkan 10 fitur dari registry dengan ai.assistant development', function () {
    expect(Feature::count())->toBe(10);
    expect(Feature::where('code', 'ai.assistant')->first()->stage)->toBe('development');
    expect(Feature::where('code', 'spd.lpj')->first()->stage)->toBe('production');
});

/* =========================================================
 * Visibilitas
 * =======================================================*/

it('fitur production terlihat oleh semua user yang berhak', function () {
    expect(feature_is_visible('spd.lpj', $this->requester))->toBeTrue();
    expect(feature_is_visible('spd.lpj', $this->developer))->toBeTrue();
});

it('fitur development hanya terlihat oleh pemegang features.manage', function () {
    expect(feature_is_visible('ai.assistant', $this->developer))->toBeTrue();
    expect(feature_is_visible('ai.assistant', $this->superadmin))->toBeTrue();  // superadmin punya semua permission
    expect(feature_is_visible('ai.assistant', $this->requester))->toBeFalse();
});

/* =========================================================
 * Aktivasi / deaktivasi
 * =======================================================*/

it('developer dapat menaikkan fitur ke production', function () {
    $feature = Feature::where('code', 'ai.assistant')->first();

    $this->actingAs($this->developer)
        ->post(route('superadmin.features.activate', $feature))
        ->assertRedirect(route('superadmin.features.index'));

    $fresh = $feature->fresh();
    expect($fresh->stage)->toBe('production');
    expect($fresh->activated_at)->not->toBeNull();
    expect($fresh->activated_by)->toBe($this->developer->id);

    // cache harus ikut ter-flush
    expect(Feature::isRestricted('ai.assistant'))->toBeFalse();
});

it('developer dapat menurunkan fitur ke development', function () {
    $feature = Feature::where('code', 'spd.lpj')->first();

    $this->actingAs($this->developer)
        ->post(route('superadmin.features.deactivate', $feature))
        ->assertRedirect();

    expect($feature->fresh()->stage)->toBe('development');
    expect(Feature::isRestricted('spd.lpj'))->toBeTrue();
});

/* =========================================================
 * Gate akses ke halaman kelola fitur
 * =======================================================*/

it('menampilkan halaman kelola fitur untuk developer', function () {
    $this->actingAs($this->developer)
        ->get(route('superadmin.features.index'))
        ->assertOk()
        ->assertSee('Tahap Fitur');
});

it('menolak user biasa membuka halaman kelola fitur', function () {
    $this->actingAs($this->requester)
        ->get(route('superadmin.features.index'))
        ->assertForbidden();
});

it('menolak kode fitur ganda', function () {
    $this->actingAs($this->developer)
        ->post(route('superadmin.features.store'), [
            'code' => 'spd.lpj',
            'name' => 'Duplikat',
        ])
        ->assertSessionHasErrors('code');
});

it('mendaftarkan fitur baru dengan default development', function () {
    $this->actingAs($this->developer)
        ->post(route('superadmin.features.store'), [
            'code' => 'spd.luar_kota',
            'name' => 'SPD Luar Kota',
        ])
        ->assertRedirect();

    expect(Feature::where('code', 'spd.luar_kota')->first()->stage)->toBe('development');
});

/* =========================================================
 * Reseed tidak menimpa keputusan developer
 * =======================================================*/

it('menjalankan ulang seeder tidak mengubah stage yang sudah diputuskan', function () {
    Feature::where('code', 'ai.assistant')->first()->activate($this->developer->id);
    expect(Feature::where('code', 'ai.assistant')->first()->stage)->toBe('production');

    $this->seed(\Database\Seeders\FeatureSeeder::class);

    expect(Feature::where('code', 'ai.assistant')->first()->stage)->toBe('production');
});
