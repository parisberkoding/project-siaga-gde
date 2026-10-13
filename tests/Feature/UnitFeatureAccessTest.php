<?php

use App\Models\Feature;
use App\Models\Role;
use App\Models\Unit;
use App\Models\UnitFeatureAccess;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);
    $this->seed(\Database\Seeders\ModuleSeeder::class);
    $this->seed(\Database\Seeders\FeatureSeeder::class);

    $this->developer = User::factory()->create(['role' => 'developers']);
    $this->developer->roles()->attach(Role::where('code', 'developers')->first()->id, ['is_primary' => true]);

    $this->requester = User::factory()->create(['role' => 'requester']);
    $this->requester->roles()->attach(Role::where('code', 'requester')->first()->id, ['is_primary' => true]);

    $this->patuha = Unit::where('code', 'PATUHA')->first();
    $this->dieng = Unit::where('code', 'DIENG')->first();
});

it('menampilkan checklist akses fitur per unit untuk developer', function () {
    $response = $this->actingAs($this->developer)
        ->get(route('superadmin.features.on_unit'))
        ->assertOk()
        ->assertSee('Akses Fitur')
        ->assertSee('Unit Patuha')
        ->assertSee('Unit Dieng')
        ->assertSee('Permohonan BBM');

    // 3 unit, masing-masing punya tabel checklist (Simpan Akses per unit)
    expect(substr_count($response->getContent(), 'Simpan Akses'))->toBe(3);
});

it('menolak user biasa membuka checklist akses fitur', function () {
    $this->actingAs($this->requester)
        ->get(route('superadmin.features.on_unit'))
        ->assertForbidden();
});

it('menyimpan matrix akses fitur untuk sebuah unit', function () {
    $feature = Feature::where('code', 'bbm.permohonan')->first();
    $feature2 = Feature::where('code', 'spd.lpj')->first();

    $this->actingAs($this->developer)
        ->post(route('superadmin.features.on_unit.update', $this->patuha), [
            'access' => [
                $feature->id => ['feature_id' => $feature->id, 'can_view' => 1, 'can_create' => 1],
                $feature2->id => ['feature_id' => $feature2->id, 'can_view' => 1, 'can_update' => 1],
            ],
        ])
        ->assertRedirect(route('superadmin.features.on_unit'));

    $row = UnitFeatureAccess::where('unit_id', $this->patuha->id)->where('feature_id', $feature->id)->first();
    expect($row->can_view)->toBeTrue();
    expect($row->can_create)->toBeTrue();
    expect($row->can_update)->toBeFalse();
    expect($row->can_delete)->toBeFalse();

    $row2 = UnitFeatureAccess::where('unit_id', $this->patuha->id)->where('feature_id', $feature2->id)->first();
    expect($row2->can_update)->toBeTrue();
    expect($row2->can_create)->toBeFalse();
});

it('checkbox yang tidak dicentang menjadi false (uncheck menghapus hak)', function () {
    $feature = Feature::where('code', 'bbm.permohonan')->first();

    // Awal: beri create = true
    UnitFeatureAccess::create([
        'unit_id' => $this->patuha->id,
        'feature_id' => $feature->id,
        'can_view' => true,
        'can_create' => true,
        'can_update' => false,
        'can_delete' => false,
    ]);

    // Simpan ulang TANPA mencentang create
    $this->actingAs($this->developer)
        ->post(route('superadmin.features.on_unit.update', $this->patuha), [
            'access' => [
                $feature->id => ['feature_id' => $feature->id, 'can_view' => 1],
            ],
        ])
        ->assertRedirect();

    $row = UnitFeatureAccess::where('unit_id', $this->patuha->id)->where('feature_id', $feature->id)->first();
    expect($row->can_view)->toBeTrue();
    expect($row->can_create)->toBeFalse();   // dicabut
});

it('matrix akses belum ditegakkan runtime (fail-open)', function () {
    expect(UnitFeatureAccess::isWiredIntoAuth())->toBeFalse();

    // Selama belum di-wire, akses apapun dianggap boleh.
    expect(UnitFeatureAccess::unitCan($this->patuha->id, 'bbm.permohonan', 'create'))->toBeTrue();
    expect(UnitFeatureAccess::unitCan($this->patuha->id, 'fitur.tidak.ada', 'delete'))->toBeTrue();
});
