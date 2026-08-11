<?php

use App\Models\Permohonan;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Services\AI\DataMart\DataMartQueryEngine;
use InvalidArgumentException;

uses(Tests\TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['ai.datamart.cache_ttl' => 0]);
    $this->engine = app(DataMartQueryEngine::class);
});

function seedPermohonans(): User
{
    $driver = User::factory()->create(['nama_lengkap' => 'Dedi']);
    $vehicleA = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 111 AAA',
        'consumerial_name' => 'Avanza',
    ]);
    $vehicleB = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => 'B 222 BBB',
        'consumerial_name' => 'Innova',
    ]);

    Permohonan::factory()->create([
        'requester_id' => $driver->id,
        'consumerial_tools_id' => $vehicleA->id,
        'gasoline_type' => 'Pertamax',
        'bill_amounts' => 300000,
        'status' => 'completed',
        'request_date' => '2026-07-01 08:00:00',
    ]);
    Permohonan::factory()->create([
        'requester_id' => $driver->id,
        'consumerial_tools_id' => $vehicleB->id,
        'gasoline_type' => 'Pertamina Dex',
        'bill_amounts' => 150000,
        'status' => 'rejected',
        'request_date' => '2026-07-10 09:00:00',
    ]);
    Permohonan::factory()->create([
        'requester_id' => $driver->id,
        'consumerial_tools_id' => $vehicleA->id,
        'gasoline_type' => 'Pertamax',
        'bill_amounts' => 500000,
        'status' => 'approved',
        'request_date' => '2026-08-05 10:00:00',
    ]);

    return $driver;
}

it('menghitung SUM bill_amounts', function () {
    seedPermohonans();

    $result = $this->engine->execute([
        'module' => 'permohonan_bbm',
        'aggregation' => ['function' => 'SUM', 'field' => 'bill_amounts'],
    ]);

    expect($result['aggregation']['value'])->toBe(950000.0)
        ->and($result['matched'])->toBe(3);
});

it('menghitung COUNT', function () {
    seedPermohonans();

    $result = $this->engine->execute([
        'module' => 'permohonan_bbm',
        'aggregation' => ['function' => 'COUNT'],
    ]);

    expect($result['aggregation']['value'])->toBe(3);
});

it('menghitung AVG, MIN, dan MAX', function () {
    seedPermohonans();

    $avg = $this->engine->execute([
        'module' => 'permohonan_bbm',
        'aggregation' => ['function' => 'AVG', 'field' => 'bill_amounts'],
    ]);
    expect($avg['aggregation']['value'])->toBe(316666.67);

    $min = $this->engine->execute([
        'module' => 'permohonan_bbm',
        'aggregation' => ['function' => 'MIN', 'field' => 'bill_amounts'],
    ]);
    expect($min['aggregation']['value'])->toBe(150000.0);

    $max = $this->engine->execute([
        'module' => 'permohonan_bbm',
        'aggregation' => ['function' => 'MAX', 'field' => 'bill_amounts'],
    ]);
    expect($max['aggregation']['value'])->toBe(500000.0);
});

it('memfilter berdasarkan gasoline_type dan status', function () {
    seedPermohonans();

    $result = $this->engine->execute([
        'module' => 'permohonan_bbm',
        'filters' => ['gasoline_type' => 'Pertamax'],
        'aggregation' => ['function' => 'SUM', 'field' => 'bill_amounts'],
    ]);

    expect($result['matched'])->toBe(2)
        ->and($result['aggregation']['value'])->toBe(800000.0);

    $status = $this->engine->execute([
        'module' => 'permohonan_bbm',
        'filters' => ['status' => 'rejected'],
        'aggregation' => ['function' => 'COUNT'],
    ]);
    expect($status['matched'])->toBe(1);
});

it('memfilter rentang tanggal (date_from / date_to)', function () {
    seedPermohonans();

    $result = $this->engine->execute([
        'module' => 'permohonan_bbm',
        'filters' => ['date_from' => '2026-07-01', 'date_to' => '2026-07-31'],
        'aggregation' => ['function' => 'COUNT'],
    ]);

    expect($result['matched'])->toBe(2);
});

it('menyortir dan membatasi (top-k)', function () {
    seedPermohonans();

    $result = $this->engine->execute([
        'module' => 'permohonan_bbm',
        'sort_by' => 'bill_amounts',
        'sort_dir' => 'desc',
        'limit' => 2,
    ]);

    expect($result['records'])->toHaveCount(2)
        ->and($result['records'][0]['bill_amounts'])->toBe(500000.0)
        ->and($result['truncated'])->toBeTrue()
        ->and($result['matched'])->toBe(3);
});

it('mengelompokkan berdasarkan status', function () {
    seedPermohonans();

    $result = $this->engine->execute([
        'module' => 'permohonan_bbm',
        'group_by' => 'status',
        'aggregation' => ['function' => 'COUNT'],
    ]);

    $groups = collect($result['groups'])->keyBy('group');
    expect($groups['completed']['count'])->toBe(1)
        ->and($groups['approved']['count'])->toBe(1)
        ->and($groups['rejected']['count'])->toBe(1);
});

it('menolak field filter yang tidak diizinkan', function () {
    seedPermohonans();

    expect(fn () => $this->engine->execute([
        'module' => 'permohonan_bbm',
        'filters' => ['kolom_rahasia' => 'x'],
    ]))->toThrow(InvalidArgumentException::class);
});

it('menolak fungsi agregasi yang tidak dikenal', function () {
    seedPermohonans();

    expect(fn () => $this->engine->execute([
        'module' => 'permohonan_bbm',
        'aggregation' => ['function' => 'DELETE', 'field' => 'bill_amounts'],
    ]))->toThrow(InvalidArgumentException::class);
});

it('menolak module yang tidak dikenal', function () {
    seedPermohonans();

    expect(fn () => $this->engine->execute(['module' => 'hacker_module']))
        ->toThrow(\RuntimeException::class);
});
