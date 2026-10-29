<?php

use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleChecking;
use App\Models\VehicleCheckingDetail;
use App\Models\VehiclePart;
use Database\Seeders\RbacSeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\VehiclePartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->seed(RbacSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->seed(VehiclePartSeeder::class);

    $this->unit = Unit::where('code', 'PATUHA')->firstOrFail();

    $this->dispatcher = User::factory()->create([
        'role'    => 'dispatcher',
        'unit_id' => $this->unit->id,
    ]);
    $this->dispatcher->roles()->attach(
        Role::where('code', 'dispatcher')->firstOrFail()->id,
        ['is_primary' => true]
    );

    $this->driver = User::factory()->create([
        'role'    => 'requester',
        'unit_id' => $this->unit->id,
    ]);

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 7002 PTH',
        'is_active'        => true,
        'unit_id'          => $this->unit->id,
    ]);

    $this->cases = [
        [
            'create'    => 'dispatcher.daily-check.create',
            'store'     => 'dispatcher.daily-check.store',
            'index'     => 'dispatcher.daily-check.index',
            'frequency' => 'daily',
            'group'     => 'routine',
            'status'    => 'ok',
        ],
        [
            'create'    => 'dispatcher.weekly-check.create',
            'store'     => 'dispatcher.weekly-check.store',
            'index'     => 'dispatcher.weekly-check.index',
            'frequency' => 'weekly',
            'group'     => 'routine',
            'status'    => 'ok',
        ],
        [
            'create'    => 'dispatcher.monthly-check.create',
            'store'     => 'dispatcher.monthly-check.store',
            'index'     => 'dispatcher.monthly-check.index',
            'frequency' => 'monthly',
            'group'     => 'routine',
            'status'    => 'ok',
        ],
        [
            'create'    => 'dispatcher.body-check.create',
            'store'     => 'dispatcher.body-check.store',
            'index'     => 'dispatcher.body-check.index',
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

    $this->sidePhotos = function (): array {
        $photos = [];

        foreach (['depan', 'belakang', 'kiri', 'kanan'] as $index => $zone) {
            $photos[$index] = [
                'photo' => UploadedFile::fake()->image($zone . '.jpg', 20, 20),
                'zone'  => $zone,
            ];
        }

        return $photos;
    };
});

it('menolak payload pengecekan yang hanya memuat sebagian item aktif', function () {
    foreach ($this->cases as $case) {
        $allItems = ($this->itemsFor)($case);
        $subset   = array_slice($allItems, 0, count($allItems) - 1, true);

        $payload = [
            'vehicle_id'        => $this->vehicle->id,
            'current_driver_id' => $this->driver->id,
            'items'             => $subset,
        ];

        if ($case['group'] === 'body') {
            $payload['damage_photos'] = ($this->sidePhotos)();
        }

        $response = $this->actingAs($this->dispatcher)
            ->from(route($case['create']))
            ->post(route($case['store']), $payload);

        $response->assertRedirect(route($case['create']));
        $response->assertSessionHasErrors('items');
        expect(session('errors')->first('items'))->toContain('belum lengkap');
    }

    expect(VehicleChecking::count())->toBe(0);
    expect(VehicleCheckingDetail::count())->toBe(0);
});

it('menerima form pengecekan lengkap untuk harian, mingguan, bulanan, dan body', function () {
    foreach ($this->cases as $index => $case) {
        // Satu kendaraan per jenis pengecekan agar setiap alur diuji terpisah.
        $vehicle = VehicleAndToolsConsumers::factory()->create([
            'consumerial_code' => 'B 71' . str_pad((string) $index, 2, '0', STR_PAD_LEFT) . ' PTH',
            'is_active'        => true,
            'unit_id'          => $this->unit->id,
        ]);

        $payload = [
            'vehicle_id'        => $vehicle->id,
            'current_driver_id' => $this->driver->id,
            'items'             => ($this->itemsFor)($case),
        ];

        if ($case['group'] === 'body') {
            $payload['damage_photos'] = ($this->sidePhotos)();
        }

        $this->actingAs($this->dispatcher)
            ->post(route($case['store']), $payload)
            ->assertSessionMissing('error')
            ->assertRedirect(route($case['index']))
            ->assertSessionHas('success');
    }

    expect(VehicleCheckingDetail::count())->toBe(4);
});

it('menolak item aktif yang tidak memiliki status valid', function () {
    $case     = $this->cases[0];
    $allItems = ($this->itemsFor)($case);

    $firstPartId = array_key_first($allItems);
    unset($allItems[$firstPartId]['status']);

    $response = $this->actingAs($this->dispatcher)
        ->from(route($case['create']))
        ->post(route($case['store']), [
            'vehicle_id'        => $this->vehicle->id,
            'current_driver_id' => $this->driver->id,
            'items'             => $allItems,
        ]);

    $response->assertSessionHasErrors('items');
    expect(session('errors')->first('items'))->toContain('belum lengkap');
    expect(VehicleCheckingDetail::count())->toBe(0);
});
