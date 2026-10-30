<?php

use App\Models\EmployeeAlias;
use App\Models\EmployeeDefault;
use App\Models\Role;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Services\Lunch\NameNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * BUG-LNC-001 — tambah karyawan tanpa alias tidak boleh 500, dan
 * penyimpanan karyawan + alias dibungkus transaksi.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);

    $this->unit = Unit::where('code', 'PATUHA')->firstOrFail();
    $this->site = Site::where('code', 'PTH')->firstOrFail();

    $this->ga = User::factory()->create([
        'role' => 'general_affairs',
        'unit_id' => $this->unit->id,
    ]);
    $this->ga->roles()->attach(
        Role::where('code', 'general_affairs')->firstOrFail()->id,
        ['is_primary' => true]
    );
});

it('menambah karyawan tanpa field alias tetap berhasil', function () {
    $response = $this->actingAs($this->ga)
        ->post(route('lunch.employee-defaults.store'), [
            'employee_name' => 'Budi Tanpa Alias',
            'unit_id' => $this->unit->id,
            'default_site_id' => $this->site->id,
        ]);

    $response->assertSessionHas('success')->assertSessionMissing('error');

    $employee = EmployeeDefault::where('employee_name', 'Budi Tanpa Alias')->first();

    expect($employee)->not->toBeNull()
        ->and(EmployeeDefault::where('employee_name', 'Budi Tanpa Alias')->count())->toBe(1)
        ->and(
            EmployeeAlias::where('employee_default_id', $employee->id)
                ->where('alias', NameNormalizer::normalize('Budi Tanpa Alias'))
                ->exists()
        )->toBeTrue();
});

it('mengubah karyawan dengan alias null tidak error dan tidak menggandakan data', function () {
    $employee = EmployeeDefault::create([
        'employee_name' => 'Siti Awal',
        'unit_id' => $this->unit->id,
        'default_site_id' => $this->site->id,
        'verification_status' => 'verified',
        'source' => 'admin',
    ]);

    $response = $this->actingAs($this->ga)
        ->put(route('lunch.employee-defaults.update', $employee), [
            'employee_name' => 'Siti Diperbarui',
            'unit_id' => $this->unit->id,
            'default_site_id' => $this->site->id,
            'aliases' => null,
        ]);

    $response->assertSessionHas('success')->assertSessionMissing('error');

    expect(EmployeeDefault::count())->toBe(1)
        ->and($employee->fresh()->employee_name)->toBe('Siti Diperbarui');
});
