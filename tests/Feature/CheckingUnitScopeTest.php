<?php

use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleCheckingDetail;
use App\Models\VehiclePart;
use Database\Seeders\RbacSeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\VehiclePartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RbacSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->seed(VehiclePartSeeder::class);

    $this->unitA = Unit::where('code', 'PATUHA')->firstOrFail();
    $this->unitB = Unit::where('code', 'DIENG')->firstOrFail();

    $this->dispatcher = User::factory()->create([
        'role'    => 'dispatcher',
        'unit_id' => $this->unitA->id,
    ]);
    $this->dispatcher->roles()->attach(
        Role::where('code', 'dispatcher')->firstOrFail()->id,
        ['is_primary' => true]
    );

    $this->driverA = User::factory()->create([
        'role'         => 'requester',
        'unit_id'      => $this->unitA->id,
        'nama_lengkap' => 'Driver Unit Patuha',
    ]);
    $this->driverB = User::factory()->create([
        'role'         => 'requester',
        'unit_id'      => $this->unitB->id,
        'nama_lengkap' => 'Driver Unit Dieng',
    ]);

    $this->vehicleA = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 7001 PTH',
        'consumerial_name' => 'Kendaraan Patuha',
        'is_active'        => true,
        'unit_id'          => $this->unitA->id,
    ]);
    $this->vehicleB = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'D 9941 AI',
        'consumerial_name' => 'Kendaraan Dieng',
        'is_active'        => true,
        'unit_id'          => $this->unitB->id,
    ]);

    $this->cases = [
        [
            'create'    => 'dispatcher.daily-check.create',
            'store'     => 'dispatcher.daily-check.store',
            'frequency' => 'daily',
            'group'     => 'routine',
            'status'    => 'ok',
        ],
        [
            'create'    => 'dispatcher.weekly-check.create',
            'store'     => 'dispatcher.weekly-check.store',
            'frequency' => 'weekly',
            'group'     => 'routine',
            'status'    => 'ok',
        ],
        [
            'create'    => 'dispatcher.monthly-check.create',
            'store'     => 'dispatcher.monthly-check.store',
            'frequency' => 'monthly',
            'group'     => 'routine',
            'status'    => 'ok',
        ],
        [
            'create'    => 'dispatcher.body-check.create',
            'store'     => 'dispatcher.body-check.store',
            'frequency' => 'monthly',
            'group'     => 'body',
            'status'    => 'baik',
        ],
    ];

    $this->itemsFor = function (array $case): array {
        $query = VehiclePart::active();

        if ($case['group'] === 'body') {
            $query->body();
        } else {
            $query->routine()->{$case['frequency']}();
        }

        return $query->get()
            ->mapWithKeys(fn (VehiclePart $part) => [
                $part->id => [
                    'status'            => $case['status'],
                    'needs_replacement' => 0,
                ],
            ])
            ->all();
    };

    $this->sidePhotos = function (array $zones = ['depan', 'belakang', 'kiri', 'kanan']): array {
        $photos = [];

        foreach ($zones as $index => $zone) {
            $photos[$index] = [
                'photo' => UploadedFile::fake()->image($zone . '.jpg', 20, 20),
                'zone'  => $zone,
            ];
        }

        return $photos;
    };
});

it('membatasi pilihan kendaraan dan driver di form pengecekan sesuai unit dispatcher', function () {
    foreach ($this->cases as $case) {
        $response = $this->actingAs($this->dispatcher)->get(route($case['create']));

        $response->assertOk();
        $response->assertSee($this->vehicleA->consumerial_code);
        $response->assertDontSee($this->vehicleB->consumerial_code);
        $response->assertSee($this->driverA->nama_lengkap);
        $response->assertDontSee($this->driverB->nama_lengkap);
    }
});

it('menolak penyimpanan kendaraan dari unit lain di semua jenis pengecekan', function () {
    foreach ($this->cases as $case) {
        $payload = [
            'vehicle_id'        => $this->vehicleB->id,
            'current_driver_id' => $this->driverA->id,
            'items'             => ($this->itemsFor)($case),
        ];

        if ($case['group'] === 'body') {
            $payload['damage_photos'] = ($this->sidePhotos)();
        }

        $response = $this->actingAs($this->dispatcher)
            ->from(route($case['create']))
            ->post(route($case['store']), $payload);

        $response->assertRedirect(route($case['create']));
        $response->assertSessionHasErrors('vehicle_id');
        expect(session('errors')->first('vehicle_id'))->toContain('di luar unit Anda');
    }

    expect(VehicleCheckingDetail::count())->toBe(0);
});

it('menolak penyimpanan driver dari unit lain di semua jenis pengecekan', function () {
    foreach ($this->cases as $case) {
        $payload = [
            'vehicle_id'        => $this->vehicleA->id,
            'current_driver_id' => $this->driverB->id,
            'items'             => ($this->itemsFor)($case),
        ];

        if ($case['group'] === 'body') {
            $payload['damage_photos'] = ($this->sidePhotos)();
        }

        $response = $this->actingAs($this->dispatcher)
            ->from(route($case['create']))
            ->post(route($case['store']), $payload);

        $response->assertRedirect(route($case['create']));
        $response->assertSessionHasErrors('current_driver_id');
        expect(session('errors')->first('current_driver_id'))->toContain('di luar unit Anda');
    }

    expect(VehicleCheckingDetail::count())->toBe(0);
});

it('mengizinkan dispatcher global melihat kendaraan dan driver semua unit', function () {
    $globalDispatcher = User::factory()->create([
        'role'    => 'dispatcher',
        'unit_id' => null,
    ]);
    $globalDispatcher->roles()->attach(
        Role::where('code', 'dispatcher')->firstOrFail()->id,
        ['is_primary' => true]
    );

    $response = $this->actingAs($globalDispatcher)->get(route('dispatcher.daily-check.create'));

    $response->assertOk();
    $response->assertSee($this->vehicleA->consumerial_code);
    $response->assertSee($this->vehicleB->consumerial_code);
    $response->assertSee($this->driverA->nama_lengkap);
    $response->assertSee($this->driverB->nama_lengkap);
});
