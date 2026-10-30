<?php

use App\Models\Permohonan;
use App\Models\Role;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * BUG-SEC-002 — double submit (klik ganda / koneksi lambat) tidak boleh
 * membuat dua permohonan BBM.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->requester = User::factory()->create(['role' => 'requester']);
    $this->requester->roles()->attach(
        Role::where('code', 'requester')->firstOrFail()->id,
        ['is_primary' => true]
    );

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'Kendaraan',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $this->payload = [
        'vehicle_id'      => $this->vehicle->id,
        'gasoline_type'   => 'Pertamax',
        'bill_payment'    => 300000,
        'requester_notes' => 'Uji double submit',
    ];
});

it('hanya membuat satu permohonan saat payload sama dikirim dua kali', function () {
    $this->actingAs($this->requester)
        ->post(route('requester-create'), $this->payload)
        ->assertSessionHas('success');

    $this->actingAs($this->requester)
        ->from(route('requester-index'))
        ->post(route('requester-create'), $this->payload)
        ->assertSessionHas('error');

    expect(session('error'))->toContain('baru saja dikirim');
    expect(Permohonan::where('requester_id', $this->requester->id)->count())->toBe(1);
});

it('mengizinkan permohonan berbeda dari user yang sama', function () {
    $this->actingAs($this->requester)
        ->post(route('requester-create'), $this->payload)
        ->assertSessionHas('success');

    $this->actingAs($this->requester)
        ->post(route('requester-create'), array_merge($this->payload, [
            'bill_payment'    => 350000,
            'requester_notes' => 'Nominal berbeda',
        ]))
        ->assertSessionHas('success');

    expect(Permohonan::where('requester_id', $this->requester->id)->count())->toBe(2);
});

it('menolak request kedua dengan idempotency key yang sama', function () {
    $key = (string) \Illuminate\Support\Str::uuid();

    $this->actingAs($this->requester)
        ->post(route('requester-create'), array_merge($this->payload, [
            'idempotency_key' => $key,
        ]))
        ->assertSessionHas('success');

    // Payload sengaja dibedakan agar yang menolak adalah idempotency key,
    // bukan dedup nominal 60 detik.
    $this->actingAs($this->requester)
        ->from(route('requester-index'))
        ->post(route('requester-create'), array_merge($this->payload, [
            'bill_payment'    => 350000,
            'requester_notes' => 'Kiriman ulang dengan key sama',
            'idempotency_key' => $key,
        ]))
        ->assertSessionHas('error');

    expect(session('error'))->toContain('sudah atau sedang diproses');
    expect(Permohonan::where('requester_id', $this->requester->id)->count())->toBe(1);
});

it('mengizinkan idempotency key berbeda untuk permohonan berbeda', function () {
    $this->actingAs($this->requester)
        ->post(route('requester-create'), array_merge($this->payload, [
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ]))
        ->assertSessionHas('success');

    $this->actingAs($this->requester)
        ->post(route('requester-create'), array_merge($this->payload, [
            'bill_payment'    => 350000,
            'requester_notes' => 'Key berbeda',
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ]))
        ->assertSessionHas('success');

    expect(Permohonan::where('requester_id', $this->requester->id)->count())->toBe(2);
});
