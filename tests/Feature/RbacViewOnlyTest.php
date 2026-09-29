<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
});

function userWithRole(string $code): User
{
    $user = User::factory()->create(['role' => $code]);
    $role = Role::where('code', $code)->firstOrFail();
    $user->roles()->attach($role->id, ['is_primary' => true]);

    return $user->fresh();
}

it('mengizinkan view_only membuka halaman baca yang diizinkan', function () {
    $user = userWithRole('view_only');

    $this->actingAs($user)->get(route('pelaporan.index'))->assertStatus(200);
    $this->actingAs($user)->get(route('requester-history'))->assertStatus(200);
    $this->actingAs($user)->get(route('requester.lpj.index'))->assertStatus(200);
    $this->actingAs($user)->get(route('vehicles'))->assertStatus(200);
    $this->actingAs($user)->get(route('lunch.dashboard'))->assertStatus(200);
});

it('menyembunyikan tombol aksi untuk view_only', function () {
    $user = userWithRole('view_only');

    // Halaman daftar kendaraan: tanpa tombol Tambah/Edit/Hapus.
    $this->actingAs($user)->get(route('vehicles'))
        ->assertStatus(200)
        ->assertDontSee(route('vehicles.create'), false)
        ->assertDontSee(route('vehicles.edit', 1), false);

    // Dashboard lunch: tanpa tombol Generate forecast.
    $this->actingAs($user)->get(route('lunch.dashboard'))
        ->assertStatus(200)
        ->assertDontSee(route('lunch.forecasts.generate'), false);
});

it('menyembunyikan menu AI Assistant dari sidebar view_only', function () {
    $user = userWithRole('view_only');

    $this->actingAs($user)->get(route('pelaporan.index'))
        ->assertStatus(200)
        ->assertDontSee('AI Assistant (Beta)', false);

    $manager = userWithRole('manager');
    $this->actingAs($manager)->get(route('pelaporan.index'))
        ->assertStatus(200)
        ->assertSee('AI Assistant (Beta)', false);
});

it('memblokir view_only dari aksi tulis BBM', function () {
    $user = userWithRole('view_only');

    $this->actingAs($user)
        ->post(route('requester-create'), [])
        ->assertStatus(403);

    $this->actingAs($user)
        ->post(route('pelaporan.operational.store'), [])
        ->assertStatus(403);
});

it('memblokir view_only dari aksi tulis SPD/LPJ', function () {
    $user = userWithRole('view_only');

    $this->actingAs($user)
        ->post(route('requester.lpj.store'), [])
        ->assertStatus(403);

    $this->actingAs($user)
        ->post(route('requester.perpanjangan.store'), [])
        ->assertStatus(403);
});

it('memblokir view_only dari aksi tulis Lunch', function () {
    $user = userWithRole('view_only');

    $this->actingAs($user)
        ->post(route('lunch.forecasts.generate'), [])
        ->assertStatus(403);

    $this->actingAs($user)
        ->post(route('lunch.guests.store'), [])
        ->assertStatus(403);

    $this->actingAs($user)
        ->post(route('lunch.self-booking.store'), [])
        ->assertStatus(403);
});

it('memblokir view_only dari manajemen kendaraan dan user', function () {
    $user = userWithRole('view_only');

    $this->actingAs($user)->get(route('vehicles.create'))->assertStatus(403);
    $this->actingAs($user)->post(route('vehicles.store'), [])->assertStatus(403);
    $this->actingAs($user)->get(route('users'))->assertStatus(403);
    $this->actingAs($user)->post(route('users.store'), [])->assertStatus(403);
});

it('memblokir view_only dari AI Assistant', function () {
    $user = userWithRole('view_only');

    $this->actingAs($user)->get(route('ai-assistant.index'))->assertStatus(403);
    $this->actingAs($user)->post(route('ai-assistant.send'), [])->assertStatus(403);
});

it('mengizinkan manager mengakses AI Assistant', function () {
    $user = userWithRole('manager');

    $this->actingAs($user)->get(route('ai-assistant.index'))->assertStatus(200);
});

it('tetap memblokir requester dari AI Assistant', function () {
    $user = userWithRole('requester');

    $this->actingAs($user)->get(route('ai-assistant.index'))->assertStatus(403);
});
