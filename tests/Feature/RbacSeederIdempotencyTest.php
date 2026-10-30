<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * BUG-RBAC-002 — RbacSeeder harus idempoten: seed ulang tidak boleh
 * menambahkan role kedua, dan role `general_affairs`/`developers` harus
 * dipetakan ke kode RBAC-nya sendiri.
 */
uses(RefreshDatabase::class);

it('seed ulang tidak menambah role kedua untuk user lama', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $roles = $user->fresh()->roles()->pluck('code')->all();

    expect($roles)->toBe(['general_affairs']);
});

it('memetakan general_affairs dan developers ke role yang sama, bukan view_only', function () {
    $ga = User::factory()->create(['role' => 'general_affairs']);
    $developer = User::factory()->create(['role' => 'developers']);

    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\RbacSeeder::class);

    expect($ga->fresh()->roles()->pluck('code')->all())->toBe(['general_affairs'])
        ->and($developer->fresh()->roles()->pluck('code')->all())->toBe(['developers']);
});

it('Admin GA mendapat permission lpj.export untuk export LPJ', function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $role = Role::where('code', 'general_affairs')->firstOrFail();

    expect($role->permissions()->where('code', 'lpj.export')->exists())->toBeTrue();
});
