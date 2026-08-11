<?php

use App\Models\MasterGasoline;
use App\Models\Permohonan;
use App\Models\Site;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleDetail;
use App\Services\AI\DataMart\DataMartManager;
use App\Services\AI\DataMart\Modules\DriverDataMart;
use App\Services\AI\DataMart\Modules\GasolineDataMart;
use App\Services\AI\DataMart\Modules\PermohonanDataMart;
use App\Services\AI\DataMart\Modules\VehicleDataMart;

uses(Tests\TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['ai.datamart.cache_ttl' => 0]);
    $this->manager = app(DataMartManager::class);
});

function makeVehicle(string $code, string $name, array $overrides = []): VehicleAndToolsConsumers
{
    return VehicleAndToolsConsumers::factory()->create(array_merge([
        'consumerial_type' => 'KENDARAAN',
        'consumerial_code' => $code,
        'consumerial_name' => $name,
        'is_approved' => true,
        'is_active' => true,
    ], $overrides));
}

it('memiliki skema standar yang konsisten untuk setiap module', function () {
    foreach (['permohonan_bbm', 'vehicle', 'driver', 'gasoline'] as $module) {
        $data = $this->manager->build($module);

        expect($data)->toHaveKeys([
            'module', 'version', 'generated_at', 'description',
            'keywords', 'entities', 'dimensions', 'measures',
            'summary', 'records',
        ])
            ->and($data['module'])->toBe($module)
            ->and($data['version'])->toBeString()
            ->and($data['generated_at'])->toBeString()
            ->and($data['dimensions'])->toBeArray()
            ->and($data['measures'])->toBeArray();
    }
});

it('menghasilkan summary yang benar untuk permohonan', function () {
    $requester = User::factory()->create(['nama_lengkap' => 'Ahmad']);
    $vehicle = makeVehicle('B 1234 ABC', 'Avanza');

    Permohonan::factory()->count(3)->create([
        'requester_id' => $requester->id,
        'consumerial_tools_id' => $vehicle->id,
        'status' => 'completed',
        'bill_amounts' => 300000,
    ]);
    Permohonan::factory()->create([
        'requester_id' => $requester->id,
        'consumerial_tools_id' => $vehicle->id,
        'status' => 'rejected',
        'bill_amounts' => 150000,
    ]);

    $mart = app(PermohonanDataMart::class);
    $records = $mart->records();
    $summary = $mart->summary($records);

    expect($summary['total_records'])->toBe(4)
        ->and($summary['completed'])->toBe(3)
        ->and($summary['rejected'])->toBe(1)
        ->and($summary['total_expense'])->toBe(900000.0)
        ->and($summary['total_nominal'])->toBe(1050000.0);
});

it('module permohonan menyertakan entity vehicle dan site', function () {
    $site = Site::where('code', 'PMU')->firstOrFail();
    $requester = User::factory()->create(['nama_lengkap' => 'Budi']);
    $vehicle = makeVehicle('B 222 YYY', 'Innova');

    VehicleDetail::factory()->create([
        'vehicle_and_tools_consumer_id' => $vehicle->id,
        'site_id' => $site->id,
    ]);

    Permohonan::factory()->create([
        'requester_id' => $requester->id,
        'consumerial_tools_id' => $vehicle->id,
    ]);

    $mart = app(PermohonanDataMart::class);
    $record = $mart->records()[0];

    expect($record['vehicle'])->toContain('B 222 YYY')
        ->and($record['site'])->toBe('PMU')
        ->and($record['driver'])->toBe('Budi');
});

it('module driver menghitung statistik per requester', function () {
    $driver = User::factory()->create(['nama_lengkap' => 'Citra']);
    Permohonan::factory()->count(2)->create(['requester_id' => $driver->id, 'status' => 'completed']);
    Permohonan::factory()->create(['requester_id' => $driver->id, 'status' => 'rejected']);

    $mart = app(DriverDataMart::class);
    $records = $mart->records();

    expect($records)->toHaveCount(1)
        ->and($records[0]['driver'])->toBe('Citra')
        ->and($records[0]['total_requests'])->toBe(3)
        ->and($records[0]['completed'])->toBe(2)
        ->and($records[0]['rejected'])->toBe(1)
        ->and($records[0]['report_rate'])->toBe(66.7);
});

it('module vehicle menampilkan detail teknis kendaraan', function () {
    $site = Site::where('code', 'PMU')->firstOrFail();
    $vehicle = makeVehicle('B 333 ZZZ', 'Fortuner');

    VehicleDetail::factory()->create([
        'vehicle_and_tools_consumer_id' => $vehicle->id,
        'site_id' => $site->id,
        'jenis_bahan_bakar' => 'Pertamax',
        'volume_tangki' => 80,
        'kapasitas_penumpang' => 7,
        'nominal_full_tank' => 1200000,
    ]);

    $mart = app(VehicleDataMart::class);
    $record = $mart->records()[0];

    expect($record['vehicle_code'])->toBe('B 333 ZZZ')
        ->and($record['site'])->toBe('PMU')
        ->and($record['fuel_type'])->toBe('Pertamax')
        ->and($record['tank_liter'])->toBe(80.0)
        ->and($record['kapasitas_penumpang'])->toBe(7)
        ->and($record['nominal_full_tank'])->toBe(1200000.0)
        ->and($record['is_active'])->toBeTrue();
});

it('module gasoline mengambil data dari MasterGasoline', function () {
    MasterGasoline::create([
        'province' => 'Prov. Jawa Barat',
        'province_slug' => 'jawa-barat',
        'fuel_type' => 'PERTAMAX',
        'price_per_liter' => 12000,
        'price_date' => now(),
    ]);

    $mart = app(GasolineDataMart::class);
    $record = $mart->records()[0];

    expect($record['province'])->toBe('Prov. Jawa Barat')
        ->and($record['fuel_type'])->toBe('Pertamax')
        ->and($record['price_per_liter'])->toBe(12000);
});

it('data mart kosong dilaporkan jujur tanpa data palsu', function () {
    $mart = app(PermohonanDataMart::class);
    $records = $mart->records();
    $summary = $mart->summary($records);

    expect($records)->toBe([])
        ->and($summary['total_records'])->toBe(0)
        ->and($summary['total_expense'])->toBe(0.0);
});

it('manager membangun module dan menolak module tak dikenal', function () {
    expect($this->manager->modules())->toContain('permohonan_bbm');

    expect(fn () => $this->manager->resolve('tidak_ada'))
        ->toThrow(\RuntimeException::class);
});
