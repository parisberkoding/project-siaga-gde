<?php

use App\Console\Commands\SheetSyncPull;
use App\Services\SheetSync\SheetApiClient;
use App\Services\SheetSync\SheetApiException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ============================================================
// Helper: build a fake API response matching the spec
// ============================================================
function fakeApiResponse(array $overrides = []): array
{
    return array_merge([
        'ok'         => true,
        'status'     => 200,
        'resource'   => 'test',
        'key'        => 'id',
        'page'       => 1,
        'limit'      => 500,
        'count'      => 0,
        'total'      => 0,
        'has_more'   => false,
        'next_since' => null,
        'fetched_at' => '2026-09-06T04:00:00.000Z',
        'data'       => [],
    ], $overrides);
}

// ============================================================
// Test 1: Client parses a successful response correctly
// ============================================================
it('client memparsing respons sukses dengan benar', function () {
    Http::fake([
        'script.google.com/*' => Http::response(fakeApiResponse([
            'resource' => 'gasoline_type',
            'count'    => 2,
            'total'    => 2,
            'data'     => [
                [
                    '_row'                => 1,
                    'gasoline_type_id'    => 'GASRON92',
                    'gasoline_name'       => 'Pertamax',
                    'price_per_liter_rp' => 15950,
                ],
                [
                    '_row'                => 2,
                    'gasoline_type_id'    => 'DEX01',
                    'gasoline_name'       => 'Pertamina Dex',
                    'price_per_liter_rp' => 21150,
                ],
            ],
        ]), 200),
    ]);

    $client = new SheetApiClient;
    $result = $client->fetch('gasoline_type');

    expect($result)->toBeArray()
        ->and($result['ok'])->toBeTrue()
        ->and($result['count'])->toBe(2)
        ->and($result['data'][0]['gasoline_type_id'])->toBe('GASRON92')
        ->and($result['data'][0]['_row'])->toBe(1);
});

// ============================================================
// Test 2: Client throws exception when body ok=false despite HTTP 200
// ============================================================
it('client melempar exception saat body ok false', function () {
    Http::fake([
        'script.google.com/*' => Http::response([
            'ok'     => false,
            'status' => 401,
            'error'  => 'unauthorized',
        ], 200),
    ]);

    $client = new SheetApiClient;

    expect(fn () => $client->fetch('gasoline_type'))
        ->toThrow(SheetApiException::class, 'unauthorized');
});

// ============================================================
// Test 3: Pagination stops precisely at has_more: false
// ============================================================
it('paginasi berhenti tepat saat has_more false', function () {
    $pageNum = 0;

    Http::fake(function ($request) use (&$pageNum) {
        $pageNum++;

        $page = $request['page'] ?? 1;

        if ($page == 1) {
            return Http::response(fakeApiResponse([
                'page'     => 1,
                'count'    => 2,
                'total'    => 5,
                'has_more' => true,
                'data'     => [
                    ['_row' => 1, 'id' => 1, 'name' => 'A'],
                    ['_row' => 2, 'id' => 2, 'name' => 'B'],
                ],
            ]), 200);
        }

        return Http::response(fakeApiResponse([
            'page'     => 2,
            'count'    => 3,
            'total'    => 5,
            'has_more' => false,
            'data'     => [
                ['_row' => 3, 'id' => 3, 'name' => 'C'],
                ['_row' => 4, 'id' => 4, 'name' => 'D'],
                ['_row' => 5, 'id' => 5, 'name' => 'E'],
            ],
        ]), 200);
    });

    $client = new SheetApiClient;
    $allData = [];
    $pageCount = 0;

    foreach ($client->pages('test') as $page) {
        $pageCount++;
        $allData = array_merge($allData, $page['data']);
    }

    expect($pageCount)->toBe(2)
        ->and(count($allData))->toBe(5);
});

// ============================================================
// Test 4: Ingest is idempotent — running sheet:pull twice gives same row count
// ============================================================
beforeEach(function () {
    // Run the staging migration
    $this->artisan('migrate', [
        '--path'  => 'database/migrations/2026_09_06_000001_create_sheet_sync_tables.php',
        '--realpath' => true,
    ]);
});

it('ingest bersifat idempotent', function () {
    $rows = [
        ['_row' => 1, 'gasoline_type_id' => 'GASRON92', 'gasoline_name' => 'Pertamax', 'price_per_liter_rp' => 15950],
        ['_row' => 2, 'gasoline_type_id' => 'DEX01',   'gasoline_name' => 'Pertamina Dex', 'price_per_liter_rp' => 21150],
    ];

    Http::fake([
        'script.google.com/*' => Http::response(fakeApiResponse([
            'resource' => 'gasoline_type',
            'count'    => count($rows),
            'total'    => count($rows),
            'data'     => $rows,
        ]), 200),
    ]);

    // First run
    $this->artisan('sheet:pull', ['resource' => 'gasoline_type'])
        ->assertSuccessful();

    expect(DB::table('legacy_gasoline_types')->count())->toBe(2);

    // Second run — idempotent
    $this->artisan('sheet:pull', ['resource' => 'gasoline_type'])
        ->assertSuccessful();

    expect(DB::table('legacy_gasoline_types')->count())->toBe(2);

    // Verify data still correct
    $gas = DB::table('legacy_gasoline_types')->where('gasoline_type_id', 'GASRON92')->first();
    expect($gas->gasoline_name)->toBe('Pertamax');
});

// ============================================================
// Test 5: Incremental mode sends 'since' based on stored cursor
// ============================================================
it('mode incremental mengirim since sesuai cursor', function () {
    $requestedSince = null;

    Http::fake(function (\Illuminate\Http\Client\Request $request) use (&$requestedSince) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);
        $requestedSince = $params['since'] ?? null;

        return Http::response(fakeApiResponse([
            'resource'   => 'permohonan',
            'count'      => 0,
            'total'      => 0,
            'next_since' => '2026-09-06T10:00:00.000Z',
            'data'       => [],
        ]), 200);
    });

    // Pre-set a cursor
    DB::table('sheet_sync_states')->insert([
        'resource'       => 'permohonan',
        'cursor'         => '2026-09-01T00:00:00.000Z',
        'last_synced_at' => now(),
        'last_row_count' => 0,
    ]);

    $this->artisan('sheet:pull', ['resource' => 'permohonan'])
        ->assertSuccessful();

    expect($requestedSince)->toBe('2026-09-01T00:00:00.000Z');
});

// ============================================================
// Test 6: --full ignores stored cursor
// ============================================================
it('--full tidak mengirim since', function () {
    $requestedSince = 'sent-something';

    Http::fake(function (\Illuminate\Http\Client\Request $request) use (&$requestedSince) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);
        $requestedSince = $params['since'] ?? null;

        return Http::response(fakeApiResponse([
            'resource'   => 'permohonan',
            'count'      => 0,
            'total'      => 0,
            'next_since' => null,
            'data'       => [],
        ]), 200);
    });

    DB::table('sheet_sync_states')->insert([
        'resource'       => 'permohonan',
        'cursor'         => '2026-09-01T00:00:00.000Z',
        'last_synced_at' => now(),
        'last_row_count' => 0,
    ]);

    $this->artisan('sheet:pull', ['resource' => 'permohonan', '--full' => true])
        ->assertSuccessful();

    expect($requestedSince)->toBeNull();
});

// ============================================================
// Test 7: Cursor not updated on failed request
// ============================================================
it('cursor tidak terupdate saat request gagal', function () {
    $callCount = 0;

    Http::fake(function () use (&$callCount) {
        $callCount++;
        // Fail after first page
        if ($callCount > 1) {
            return Http::response([
                'ok'     => false,
                'status' => 500,
                'error'  => 'internal error',
            ], 200);
        }

        return Http::response(fakeApiResponse([
            'resource'   => 'permohonan',
            'count'      => 1,
            'total'      => 2,
            'has_more'   => true,
            'next_since' => '2026-09-06T11:00:00.000Z',
            'data'       => [
                ['_row' => 1, 'request_id' => 'REQ1', 'driver_id' => 1],
            ],
        ]), 200);
    });

    // Pre-set cursor
    DB::table('sheet_sync_states')->insert([
        'resource'       => 'permohonan',
        'cursor'         => '2026-09-01T00:00:00.000Z',
        'last_synced_at' => now(),
        'last_row_count' => 0,
    ]);

    $this->artisan('sheet:pull', ['resource' => 'permohonan']);
    // Should fail but not crash

    $state = DB::table('sheet_sync_states')->where('resource', 'permohonan')->first();
    expect($state->cursor)->toBe('2026-09-01T00:00:00.000Z');
});

// ============================================================
// Test 8: --dry-run does not write any rows
// ============================================================
it('--dry-run tidak menulis satu baris pun', function () {
    Http::fake([
        'script.google.com/*' => Http::response(fakeApiResponse([
            'resource' => 'gasoline_type',
            'count'    => 2,
            'total'    => 2,
            'data'     => [
                ['_row' => 1, 'gasoline_type_id' => 'GASRON92', 'gasoline_name' => 'Pertamax', 'price_per_liter_rp' => 15950],
                ['_row' => 2, 'gasoline_type_id' => 'DEX01',   'gasoline_name' => 'Pertamina Dex', 'price_per_liter_rp' => 21150],
            ],
        ]), 200),
    ]);

    $this->artisan('sheet:pull', ['resource' => 'gasoline_type', '--dry-run' => true])
        ->assertSuccessful();

    expect(DB::table('legacy_gasoline_types')->count())->toBe(0);
});

// ============================================================
// Test 9: Null values and empty data handled without error
// ============================================================
it('baris dengan null dan data kosong ditangani tanpa error', function () {
    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);
        $resource = $params['resource'] ?? '';

        if ($resource === 'gasoline_type') {
            return Http::response(fakeApiResponse([
                'resource' => 'gasoline_type',
                'count'    => 1,
                'total'    => 1,
                'data'     => [
                    [
                        '_row'                => 1,
                        'gasoline_type_id'    => 'NULLTEST',
                        'gasoline_name'       => null,
                        'price_per_liter_rp' => null,
                    ],
                ],
            ]), 200);
        }

        // Empty data for all other resources
        return Http::response(fakeApiResponse([
            'resource' => $resource,
            'count'    => 0,
            'total'    => 0,
            'data'     => [],
        ]), 200);
    });

    $this->artisan('sheet:pull')
        ->assertSuccessful();

    // Only gasoline_type should have data
    expect(DB::table('legacy_gasoline_types')->count())->toBe(1)
        ->and(DB::table('legacy_users')->count())->toBe(0)
        ->and(DB::table('legacy_vehicles')->count())->toBe(0)
        ->and(DB::table('legacy_permohonans')->count())->toBe(0);

    // Verify null fields stored as null
    $row = DB::table('legacy_gasoline_types')->where('gasoline_type_id', 'NULLTEST')->first();
    expect($row->gasoline_name)->toBeNull()
        ->and($row->price_per_liter_rp)->toBeNull();
});

// ============================================================
// Test 10: Users resource has no password field (blocked at API level)
// ============================================================
it('users resource tidak mengandung password', function () {
    Http::fake([
        'script.google.com/*' => Http::response(fakeApiResponse([
            'resource' => 'users',
            'count'    => 1,
            'total'    => 1,
            'data'     => [
                [
                    '_row'     => 1,
                    'id'       => 1,
                    'username' => 'testuser',
                    'fullname' => 'Test User',
                    'role'     => 'driver',
                    'email'    => 'test@example.com',
                ],
            ],
        ]), 200),
    ]);

    $this->artisan('sheet:pull', ['resource' => 'users'])
        ->assertSuccessful();

    $user = DB::table('legacy_users')->where('legacy_id', 1)->first();
    expect($user->username)->toBe('testuser')
        ->and($user->fullname)->toBe('Test User')
        ->and($user->role)->toBe('driver')
        ->and($user->email)->toBe('test@example.com')
        // Ensure password is NOT present — the API blocks it
        ->and(property_exists($user, 'password'))->toBeFalse();
});

// ============================================================
// Test 11: All six resources work end-to-end
// ============================================================
it('semua 6 resource bisa ditarik end-to-end', function () {
    $resources = [
        'gasoline_type' => [
            ['_row' => 1, 'gasoline_type_id' => 'A', 'gasoline_name' => 'Type A', 'price_per_liter_rp' => 10000],
        ],
        'users' => [
            ['_row' => 1, 'id' => 1, 'username' => 'u1', 'fullname' => 'User 1', 'role' => 'driver', 'email' => null],
        ],
        'vehicles' => [
            ['_row' => 1, 'id' => 1, 'plat_nomer' => 'D 1234 AB', 'merk' => 'Toyota', 'jenis' => 'Kendaraan'],
        ],
        'permohonan' => [
            ['_row' => 1, 'request_id' => 'REQ1', 'driver_id' => 1, 'vehicle_id' => '1', 'gasoline_type' => 'Pertamax', 'bill_payments' => 500000, 'status' => 'completed', 'request_at' => '2026-01-01T00:00:00Z'],
        ],
        'transaction_proof' => [
            ['_row' => 1, 'proof_id' => 'PRF1', 'request_id' => 'REQ1', 'voucher_code' => 'VCH1', 'km_terakhir' => 1000, 'created_at' => '2026-01-02T00:00:00Z'],
        ],
        'vehicle_health' => [
            ['_row' => 1, 'id' => 1, 'vehicle_id' => 1, 'ltp_date' => '2026-01-01T00:00:00Z', 'lat' => null, 'long' => null],
        ],
    ];

    Http::fake(function (\Illuminate\Http\Client\Request $request) use ($resources) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);
        $resource = $params['resource'] ?? '';
        $rows = $resources[$resource] ?? [];

        return Http::response(fakeApiResponse([
            'resource' => $resource,
            'count'    => count($rows),
            'total'    => count($rows),
            'data'     => $rows,
        ]), 200);
    });

    $this->artisan('sheet:pull')
        ->assertSuccessful();

    expect(DB::table('legacy_gasoline_types')->count())->toBe(1)
        ->and(DB::table('legacy_users')->count())->toBe(1)
        ->and(DB::table('legacy_vehicles')->count())->toBe(1)
        ->and(DB::table('legacy_permohonans')->count())->toBe(1)
        ->and(DB::table('legacy_transaction_proofs')->count())->toBe(1)
        ->and(DB::table('legacy_vehicle_healths')->count())->toBe(1);
});

// ============================================================
// Test 12: vehicle_health long/lat renamed to longitude/latitude
// ============================================================
it('vehicle_health long di-rename ke longitude dan lat ke latitude', function () {
    Http::fake([
        'script.google.com/*' => Http::response(fakeApiResponse([
            'resource' => 'vehicle_health',
            'count'    => 1,
            'total'    => 1,
            'data'     => [
                [
                    '_row'    => 1,
                    'id'      => 1,
                    'vehicle_id' => 1,
                    'lat'     => -7.1234567,
                    'long'    => 109.1234567,
                ],
            ],
        ]), 200),
    ]);

    $this->artisan('sheet:pull', ['resource' => 'vehicle_health'])
        ->assertSuccessful();

    $record = DB::table('legacy_vehicle_healths')->where('legacy_id', 1)->first();
    expect($record->latitude)->toBe(-7.1234567)
        ->and($record->longitude)->toBe(109.1234567);
});