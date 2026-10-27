<?php

use App\Models\AiChatMessage;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleDetail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);

    $this->sitePTH   = Site::updateOrCreate(['code' => 'PTH'],    ['name' => 'Patuha',          'is_active' => true]);
    $this->siteDieng = Site::updateOrCreate(['code' => 'Dieng'],  ['name' => 'Dieng',           'is_active' => true]);

    $this->unitPatuha = Unit::where('code', 'PATUHA')->first();
    $this->unitPusat  = Unit::where('code', 'PUSAT')->first();
    $this->unitDieng  = Unit::where('code', 'DIENG')->first();

    $this->gaUnit = User::factory()->create([
        'role'    => 'general_affairs',
        'unit_id' => $this->unitPatuha->id,
    ]);
    $this->gaPusat = User::factory()->create([
        'role'    => 'general_affairs',
        'unit_id' => $this->unitPusat->id,
    ]);

    $role = Role::where('code', 'general_affairs')->first();
    $this->gaUnit->roles()->attach($role->id, ['is_primary' => true]);
    $this->gaPusat->roles()->attach($role->id, ['is_primary' => true]);

    config(['ai.datamart.enabled' => true]);
    config(['ai.datamart.cache_ttl' => 0]);
    Cache::flush();

    Http::fake([
        '*' => Http::response([
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => 'ok']],
            ],
        ], 200),
    ]);
});

function aiScopeFakeVehicle(string $code, int $siteId): VehicleAndToolsConsumers
{
    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_code' => $code,
        'is_approved'      => true,
        'unit_id'          => Site::find($siteId)?->unit_id,
    ]);
    VehicleDetail::factory()->create([
        'vehicle_and_tools_consumer_id' => $vehicle->id,
        'site_id'                       => $siteId,
    ]);

    return $vehicle;
}

it('user unit hanya melihat data BBM unitnya sendiri lewat AI assistant', function () {
    $pthVehicle = aiScopeFakeVehicle('B-PTH-AI-1', $this->sitePTH->id);
    $diengVehicle = aiScopeFakeVehicle('B-DIEN-AI-1', $this->siteDieng->id);

    $requester = User::factory()->create(['nama_lengkap' => 'Rina']);
    Permohonan::factory()->create([
        'requester_id'         => $requester->id,
        'consumerial_tools_id' => $pthVehicle->id,
        'bill_amounts'         => 100000,
        'status'               => 'completed',
        'request_date'         => now()->format('Y-m-d H:i:s'),
        'unit_id'              => $this->unitPatuha->id,
    ]);
    Permohonan::factory()->create([
        'requester_id'         => $requester->id,
        'consumerial_tools_id' => $diengVehicle->id,
        'bill_amounts'         => 999999,
        'status'               => 'completed',
        'request_date'         => now()->format('Y-m-d H:i:s'),
        'unit_id'              => $this->unitDieng->id,
    ]);

    $this->actingAs($this->gaUnit)
        ->postJson(route('ai-assistant.send'), ['message' => 'Berapa total pengeluaran BBM bulan ini?'])
        ->assertOk();

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['module'])->toBe('permohonan_bbm')
        ->and($assistant->metadata['records_matched'])->toBe(1);
});

it('GA di unit Pusat tetap dibatasi ke site unit Pusat', function () {
    $pthVehicle = aiScopeFakeVehicle('B-PTH-AI-2', $this->sitePTH->id);
    $diengVehicle = aiScopeFakeVehicle('B-DIEN-AI-2', $this->siteDieng->id);

    $requester = User::factory()->create(['nama_lengkap' => 'Rian']);
    Permohonan::factory()->create([
        'requester_id'         => $requester->id,
        'consumerial_tools_id' => $pthVehicle->id,
        'bill_amounts'         => 100000,
        'status'               => 'completed',
        'request_date'         => now()->format('Y-m-d H:i:s'),
        'unit_id'              => $this->unitPatuha->id,
    ]);
    Permohonan::factory()->create([
        'requester_id'         => $requester->id,
        'consumerial_tools_id' => $diengVehicle->id,
        'bill_amounts'         => 999999,
        'status'               => 'completed',
        'request_date'         => now()->format('Y-m-d H:i:s'),
        'unit_id'              => $this->unitDieng->id,
    ]);

    $this->actingAs($this->gaPusat)
        ->postJson(route('ai-assistant.send'), ['message' => 'Berapa total pengeluaran BBM bulan ini?'])
        ->assertOk();

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['module'])->toBe('permohonan_bbm')
        ->and($assistant->metadata['records_matched'])->toBe(0);
});

it('user unit hanya melihat kendaraan unitnya lewat AI assistant', function () {
    aiScopeFakeVehicle('B-PTH-AI-3', $this->sitePTH->id);
    aiScopeFakeVehicle('B-DIEN-AI-3', $this->siteDieng->id);

    $this->actingAs($this->gaUnit)
        ->postJson(route('ai-assistant.send'), ['message' => 'Berapa jumlah kendaraan yang terdaftar?'])
        ->assertOk();

    $assistant = AiChatMessage::where('role', 'assistant')->first();
    expect($assistant->metadata['module'])->toBe('vehicle')
        ->and($assistant->metadata['records_matched'])->toBe(1);
});

it('menyuntikkan LINGKUP DATA pada prompt untuk user unit', function () {
    aiScopeFakeVehicle('B-PTH-AI-4', $this->sitePTH->id);

    $this->actingAs($this->gaUnit)
        ->postJson(route('ai-assistant.send'), ['message' => 'Berapa total pengeluaran BBM bulan ini?'])
        ->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->body(), 'LINGKUP DATA ANDA'));
});

it('scope inUnit part replacement & checking schedule membatasi per unit', function () {
    $pthVehicle = aiScopeFakeVehicle('B-PTH-AI-5', $this->sitePTH->id);
    $diengVehicle = aiScopeFakeVehicle('B-DIEN-AI-5', $this->siteDieng->id);

    $makePart = function ($vehicle, string $code, string $nopol) {
        $checking = \App\Models\VehicleChecking::create([
            'checking_code' => 'CHK-'.strtoupper($code),
            'vehicle_id' => $vehicle->id,
            'nopol' => $nopol,
            'period_start' => now()->subDays(7)->toDateString(),
            'period_end' => now()->toDateString(),
            'generated_by' => $this->gaUnit->id,
            'status' => 'active',
        ]);
        $detail = \App\Models\VehicleCheckingDetail::create([
            'checking_detail_code' => 'CD-'.strtoupper($code).'-001',
            'vehicle_checking_id' => $checking->id,
            'type' => 'routine',
            'dispatcher_id' => $this->gaUnit->id,
            'current_driver_id' => $this->gaUnit->id,
            'checked_at' => now()->subDays(2),
        ]);

        return \App\Models\VehiclePartsReplacement::create([
            'vpr_code' => $code,
            'vehicle_checking_detail_id' => $detail->id,
            'vehicle_id' => $vehicle->id,
            'nopol' => $nopol,
            'dispatcher_id' => $this->gaUnit->id,
            'last_driver_id' => $this->gaUnit->id,
            'status' => 'completed',
            'scheduled_date' => now()->subDays(1),
        ]);
    };

    $pthPart = $makePart($pthVehicle, 'VPR-PTHU-1', 'B 1234 PTH');
    $diengPart = $makePart($diengVehicle, 'VPR-DIEN-1', 'D 5678 DNG');

    $pthCheck = \App\Models\VehicleCheckingSchedule::create([
        'schedule_code' => 'SCH-PTHU-1',
        'vehicle_id' => $pthVehicle->id,
        'check_frequency' => 'daily',
        'next_due_date' => now()->addDays(7),
    ]);
    $diengCheck = \App\Models\VehicleCheckingSchedule::create([
        'schedule_code' => 'SCH-DIEN-1',
        'vehicle_id' => $diengVehicle->id,
        'check_frequency' => 'daily',
        'next_due_date' => now()->addDays(7),
    ]);

    expect(\App\Models\VehiclePartsReplacement::inUnit($this->gaUnit->unit_id)->pluck('id'))
        ->toContain($pthPart->id)->not->toContain($diengPart->id);

    expect(\App\Models\VehicleCheckingSchedule::inUnit($this->gaUnit->unit_id)->pluck('id'))
        ->toContain($pthCheck->id)->not->toContain($diengCheck->id);
});
