<?php

use App\Models\Role;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleDetail;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    $this->superadmin->roles()->attach($role->id, ['is_primary' => true]);

    $this->patuha = Unit::where('code', 'PATUHA')->first();
});

it('menampilkan daftar site', function () {
    $this->actingAs($this->superadmin)
        ->get(route('superadmin.sites.index'))
        ->assertOk()
        ->assertSee('Site / Lokasi')
        ->assertSee('Basecamp Patuha');
});

it('menampilkan form tambah site', function () {
    $this->actingAs($this->superadmin)
        ->get(route('superadmin.sites.create'))
        ->assertOk()
        ->assertSee('Detail Site')
        ->assertSee('Unit Patuha');
});

it('membuat site baru yang wajib terhubung ke unit', function () {
    $this->actingAs($this->superadmin)
        ->post(route('superadmin.sites.store'), [
            'code' => 'SUMUR-2',
            'name' => 'Sumur Patuha 2',
            'unit_id' => $this->patuha->id,
            'province_slug' => 'jawa-barat',
            'is_active' => 1,
        ])
        ->assertRedirect(route('superadmin.sites.index'))
        ->assertSessionHas('success');

    $site = Site::where('code', 'SUMUR-2')->first();
    expect($site)->not->toBeNull();
    expect($site->unit_id)->toBe($this->patuha->id);
    expect($site->name)->toBe('Sumur Patuha 2');

    // PTH-BC adalah bagian dari Unit Patuha
    $pthBc = Site::where('code', 'PTH-BC')->first();
    expect($pthBc->unit_id)->toBe($this->patuha->id);
});

it('menolak site tanpa unit', function () {
    $this->actingAs($this->superadmin)
        ->post(route('superadmin.sites.store'), [
            'code' => 'YATIM',
            'name' => 'Site Tanpa Unit',
        ])
        ->assertSessionHasErrors('unit_id');

    expect(Site::where('code', 'YATIM')->exists())->toBeFalse();
});

it('menolak kode site duplikat', function () {
    $this->actingAs($this->superadmin)
        ->post(route('superadmin.sites.store'), [
            'code' => 'PTH-BC',
            'name' => 'Duplikat',
            'unit_id' => $this->patuha->id,
        ])
        ->assertSessionHasErrors('code');
});

it('mengupdate site dan memindahkan ke unit lain', function () {
    $site = Site::where('code', 'PTH-BC')->first();
    $dieng = Unit::where('code', 'DIENG')->first();

    $this->actingAs($this->superadmin)
        ->put(route('superadmin.sites.update', $site), [
            'name' => 'Basecamp Patuha (Updated)',
            'unit_id' => $dieng->id,
            'is_active' => 1,
        ])
        ->assertRedirect(route('superadmin.sites.index'));

    expect($site->fresh()->name)->toBe('Basecamp Patuha (Updated)');
    expect($site->fresh()->unit_id)->toBe($dieng->id);
});

it('menolak hapus site yang masih dipakai kendaraan', function () {
    $site = Site::where('code', 'PTH-BC')->first();
    VehicleDetail::factory()->create(['site_id' => $site->id]);

    $this->actingAs($this->superadmin)
        ->post(route('superadmin.sites.delete', $site))
        ->assertSessionHas('error');

    expect(Site::find($site->id))->not->toBeNull();
});

it('menghapus site yang tidak dipakai', function () {
    $site = Site::create(['code' => 'GUDANG', 'name' => 'Gudang BBM', 'unit_id' => $this->patuha->id, 'is_active' => true]);

    $this->actingAs($this->superadmin)
        ->post(route('superadmin.sites.delete', $site))
        ->assertSessionHas('success');

    expect(Site::find($site->id))->toBeNull();
});