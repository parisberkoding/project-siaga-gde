<?php

use App\Models\Permohonan;
use App\Models\Role;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->driver = User::factory()->create(['role' => 'requester']);
    $this->driver->roles()->attach(
        Role::where('code', 'requester')->first()->id,
        ['is_primary' => true]
    );

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved' => true,
        'is_active' => true,
    ]);
});

it('memuat 100 baris pertama lalu batch berikutnya sesuai offset', function () {
    Permohonan::factory()->count(105)->create([
        'requester_id' => $this->driver->id,
        'request_date' => now(),
        'consumerial_tools_id' => $this->vehicle->id,
    ]);

    $this->actingAs($this->driver)
        ->get(route('requester-history'))
        ->assertOk()
        ->assertSee('Terapkan Filter', false)
        ->assertSee('10 per halaman', false);

    $this->actingAs($this->driver)
        ->getJson(route('requester-history', ['json' => 1]))
        ->assertOk()
        ->assertJsonCount(100, 'rows')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 100);

    $this->actingAs($this->driver)
        ->getJson(route('requester-history', ['json' => 1, 'offset' => 100]))
        ->assertOk()
        ->assertJsonCount(5, 'rows')
        ->assertJsonPath('has_more', false);
});

it('membalik rentang tanggal yang terbalik dan membatasi maksimal 30 hari', function () {
    $dalamRentang = Permohonan::factory()->create([
        'requester_id' => $this->driver->id,
        'request_date' => now()->subDays(12)->setTime(10, 0),
        'consumerial_tools_id' => $this->vehicle->id,
        'request_number' => 'REQ-DALAM-RENTANG',
    ]);

    $diLuarRentang = Permohonan::factory()->create([
        'requester_id' => $this->driver->id,
        'request_date' => now()->subDays(5)->setTime(10, 0),
        'consumerial_tools_id' => $this->vehicle->id,
        'request_number' => 'REQ-DI-LUAR-RENTANG',
    ]);

    // from > to (terbalik) → harus dibaca to..from dan tetap menemukan data.
    $response = $this->actingAs($this->driver)
        ->getJson(route('requester-history', [
            'json' => 1,
            'from' => now()->subDays(12)->toDateString(),
            'to' => now()->subDays(14)->toDateString(),
        ]))
        ->assertOk();

    $numbers = collect($response->json('rows'))->pluck('request_number')->all();

    expect($numbers)->toContain($dalamRentang->request_number)
        ->and($numbers)->not->toContain($diLuarRentang->request_number);

    // Rentang > 30 hari dipotong: data 3 hari lalu berada di luar batas
    // 30 hari dari tanggal awal sehingga tidak boleh muncul.
    $terlaluLama = Permohonan::factory()->create([
        'requester_id' => $this->driver->id,
        'request_date' => now()->subDays(3)->setTime(10, 0),
        'consumerial_tools_id' => $this->vehicle->id,
        'request_number' => 'REQ-TERLALU-LAMA',
    ]);

    $response = $this->actingAs($this->driver)
        ->getJson(route('requester-history', [
            'json' => 1,
            'from' => now()->subDays(40)->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertOk();

    $numbers = collect($response->json('rows'))->pluck('request_number')->all();

    expect($numbers)->not->toContain($terlaluLama->request_number)
        ->and($numbers)->toContain($dalamRentang->request_number);
});
