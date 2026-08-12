<?php

use App\Models\EmployeeDefault;
use App\Models\Role;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    $this->superadmin->roles()->attach($role->id, ['is_primary' => true]);
});

it('mode manual membuat user + mendaftarkan ke Master Pekerja', function () {
    $this->actingAs($this->superadmin)
        ->post(route('users.store'), [
            'mode' => 'manual',
            'username' => 'budi.driver',
            'password' => 'rahasia123',
            'nama_lengkap' => 'Budi Santoso',
            'email' => 'budi@geodipa.test',
            'jabatan' => 'Driver',
            'role_code' => 'requester',
            'create_employee' => 1,
        ])
        ->assertRedirect(route('users', ['tab' => 'list']))
        ->assertSessionHas('success');

    $user = User::where('username', 'budi.driver')->first();
    expect($user)->not->toBeNull();
    expect($user->nama_lengkap)->toBe('Budi Santoso');
    expect($user->role)->toBe('requester');

    $emp = EmployeeDefault::where('user_id', $user->id)->first();
    expect($emp)->not->toBeNull();
    expect($emp->employee_name)->toBe('Budi Santoso');
});

it('mode manual tanpa create_employee tetap membuat user tanpa Master Pekerja', function () {
    $this->actingAs($this->superadmin)
        ->post(route('users.store'), [
            'mode' => 'manual',
            'username' => 'siti.ga',
            'password' => 'rahasia123',
            'nama_lengkap' => 'Siti Aminah',
            'email' => 'siti@geodipa.test',
            'role_code' => 'general_affairs',
        ])
        ->assertRedirect();

    $user = User::where('username', 'siti.ga')->first();
    expect($user)->not->toBeNull();
    expect(EmployeeDefault::where('user_id', $user->id)->exists())->toBeFalse();
});

it('mode aktivasi memilih Master Pekerja lalu membuat akun + link', function () {
    $emp = EmployeeDefault::create([
        'employee_name' => 'Joko Widodo',
        'position' => 'Driver Patuha',
        'is_active' => true,
    ]);

    $this->actingAs($this->superadmin)
        ->post(route('users.store'), [
            'mode' => 'activate',
            'employee_default_id' => $emp->id,
            'username' => 'joko.w',
            'password' => 'rahasia123',
            'email' => 'joko@geodipa.test',
            'role_code' => 'requester',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $user = User::where('username', 'joko.w')->first();
    expect($user)->not->toBeNull();
    expect($user->nama_lengkap)->toBe('Joko Widodo');
    expect($user->jabatan)->toBe('Driver Patuha');

    expect($emp->fresh()->user_id)->toBe($user->id);
});

it('mode aktivasi menolak pekerja yang sudah punya akun', function () {
    $existingUser = User::factory()->create(['role' => 'requester']);
    $emp = EmployeeDefault::create([
        'employee_name' => 'Sudah Punya Akun',
        'user_id' => $existingUser->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->superadmin)
        ->post(route('users.store'), [
            'mode' => 'activate',
            'employee_default_id' => $emp->id,
            'username' => 'baru.coba',
            'password' => 'rahasia123',
            'email' => 'baru@geodipa.test',
            'role_code' => 'requester',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(User::where('username', 'baru.coba')->exists())->toBeFalse();
});

it('menyajikan halaman create user dengan daftar pekerja belum berakun', function () {
    EmployeeDefault::create(['employee_name' => 'Pekerja Bebas', 'is_active' => true]);
    $linked = User::factory()->create(['role' => 'requester']);
    EmployeeDefault::create(['employee_name' => 'Pekerja Terlink', 'user_id' => $linked->id, 'is_active' => true]);

    $this->actingAs($this->superadmin)
        ->get(route('users.create'))
        ->assertStatus(200)
        ->assertSee('Pekerja Bebas', false)
        ->assertDontSee('Pekerja Terlink', false);
});
