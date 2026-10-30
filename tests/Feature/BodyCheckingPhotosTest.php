<?php

use App\Models\DamagePhoto;
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
        'consumerial_code' => 'B 7003 PTH',
        'is_active'        => true,
        'unit_id'          => $this->unit->id,
    ]);

    $this->items = VehiclePart::active()
        ->body()
        ->get()
        ->mapWithKeys(fn (VehiclePart $part) => [
            $part->id => ['status' => 'baik', 'needs_replacement' => 0],
        ])
        ->all();

    $this->photosFor = function (array $zones): array {
        $photos = [];

        foreach (array_values($zones) as $index => $zone) {
            $photos[$index] = [
                'photo' => UploadedFile::fake()->image($zone . '.jpg', 20, 20),
                'zone'  => $zone,
            ];
        }

        return $photos;
    };
});

it('menolak body checking yang hanya mengunggah dua foto sisi', function () {
    $response = $this->actingAs($this->dispatcher)
        ->from(route('dispatcher.body-check.create'))
        ->post(route('dispatcher.body-check.store'), [
            'vehicle_id'        => $this->vehicle->id,
            'current_driver_id' => $this->driver->id,
            'items'             => $this->items,
            'damage_photos'     => ($this->photosFor)(['depan', 'belakang']),
        ]);

    $response->assertRedirect(route('dispatcher.body-check.create'));
    $response->assertSessionHasErrors('damage_photos');
    expect(session('errors')->first('damage_photos'))
        ->toContain('Kiri')
        ->toContain('Kanan')
        ->not->toContain('Depan');

    expect(VehicleCheckingDetail::count())->toBe(0);
    expect(DamagePhoto::count())->toBe(0);
});

it('menolak body checking tanpa foto sama sekali', function () {
    $response = $this->actingAs($this->dispatcher)
        ->from(route('dispatcher.body-check.create'))
        ->post(route('dispatcher.body-check.store'), [
            'vehicle_id'        => $this->vehicle->id,
            'current_driver_id' => $this->driver->id,
            'items'             => $this->items,
        ]);

    $response->assertSessionHasErrors('damage_photos');
    expect(session('errors')->first('damage_photos'))
        ->toContain('Depan')
        ->toContain('Belakang')
        ->toContain('Kiri')
        ->toContain('Kanan');

    expect(VehicleCheckingDetail::count())->toBe(0);
});

it('menyimpan body checking saat empat foto sisi lengkap', function () {
    $response = $this->actingAs($this->dispatcher)
        ->post(route('dispatcher.body-check.store'), [
            'vehicle_id'        => $this->vehicle->id,
            'current_driver_id' => $this->driver->id,
            'items'             => $this->items,
            'damage_photos'     => ($this->photosFor)(['depan', 'belakang', 'kiri', 'kanan']),
        ]);

    $response->assertRedirect(route('dispatcher.body-check.index'));
    $response->assertSessionHas('success');

    $detail = VehicleCheckingDetail::where('type', 'body')->firstOrFail();

    expect($detail->itemResults)->toHaveCount(count($this->items));
    expect(DamagePhoto::where('vehicle_checking_detail_id', $detail->id)->count())->toBe(4);
    expect(DamagePhoto::pluck('zone')->sort()->values()->all())
        ->toBe(['belakang', 'depan', 'kanan', 'kiri']);
});

it('menampilkan empat input foto sisi wajib pada form body checking', function () {
    $response = $this->actingAs($this->dispatcher)->get(route('dispatcher.body-check.create'));

    $response->assertOk();
    $response->assertSee('Foto Empat Sisi Kendaraan');
    $response->assertSee('name="damage_photos[0][photo]"', false);
    $response->assertSee('name="damage_photos[3][photo]"', false);
    $response->assertSee('data-side-label="Depan"', false);
    $response->assertSee('data-side-label="Kanan"', false);
    $response->assertSee('required', false);
    $response->assertSee('wajib');
});

it('tidak menyisakan penanda konflik git pada view body checking', function () {
    $source = file_get_contents(resource_path('views/dispatcher/body-check/create.blade.php'));

    expect($source)
        ->not->toContain('<<<<<<<')
        ->not->toContain('>>>>>>>')
        ->not->toContain('cf7ec66');
});
