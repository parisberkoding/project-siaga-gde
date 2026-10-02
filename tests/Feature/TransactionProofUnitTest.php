<?php

use App\Models\Permohonan;
use App\Models\Transaction_Proofs;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);

    $this->patuha = Unit::where('code', 'PATUHA')->first();
    $this->dieng = Unit::where('code', 'DIENG')->first();
});

it('mewarisi unit dari permohonan saat bukti dibuat', function () {
    $vehicle = VehicleAndToolsConsumers::factory()->create();
    $requester = User::factory()->create(['role' => 'requester', 'unit_id' => $this->patuha->id]);

    $permohonan = Permohonan::factory()->create([
        'requester_id' => $requester->id,
        'consumerial_tools_id' => $vehicle->id,
        'unit_id' => $this->patuha->id,
    ]);

    $proof = Transaction_Proofs::create([
        'transaction_id' => 'TRX-TEST-1',
        'req_id' => $permohonan->id,
        'requester_id' => $requester->id,
        'voucher_number' => 'VCH-TEST-1',
        'purchase_datetime' => now(),
        'fuel_volume' => 10,
        'struk_bbm_path' => 'path/to/struk.jpg',
    ]);

    // unit_id terisi otomatis tanpa perlu disebut di create()
    expect($proof->unit_id)->toBe($this->patuha->id);
});

it('scopeInUnit menyaring lewat kolom unit_id langsung', function () {
    $vehicle = VehicleAndToolsConsumers::factory()->create();

    $r1 = User::factory()->create(['role' => 'requester', 'unit_id' => $this->patuha->id]);
    $r2 = User::factory()->create(['role' => 'requester', 'unit_id' => $this->dieng->id]);

    $p1 = Permohonan::factory()->create(['requester_id' => $r1->id, 'consumerial_tools_id' => $vehicle->id, 'unit_id' => $this->patuha->id]);
    $p2 = Permohonan::factory()->create(['requester_id' => $r2->id, 'consumerial_tools_id' => $vehicle->id, 'unit_id' => $this->dieng->id]);

    Transaction_Proofs::create(['transaction_id' => 'T1', 'req_id' => $p1->id, 'requester_id' => $r1->id, 'voucher_number' => 'V1', 'purchase_datetime' => now(), 'fuel_volume' => 1, 'struk_bbm_path' => 'x.jpg']);
    Transaction_Proofs::create(['transaction_id' => 'T2', 'req_id' => $p2->id, 'requester_id' => $r2->id, 'voucher_number' => 'V2', 'purchase_datetime' => now(), 'fuel_volume' => 1, 'struk_bbm_path' => 'x.jpg']);

    $patuhaProofs = Transaction_Proofs::inUnit($this->patuha->id)->pluck('voucher_number')->all();
    $diengProofs = Transaction_Proofs::inUnit($this->dieng->id)->pluck('voucher_number')->all();

    expect($patuhaProofs)->toBe(['V1']);
    expect($diengProofs)->toBe(['V2']);

    // null = tanpa scope
    expect(Transaction_Proofs::inUnit(null)->count())->toBe(2);
});

it('fallback ke relasi permohonan untuk baris lama yang belum ter-backfill', function () {
    $vehicle = VehicleAndToolsConsumers::factory()->create();
    $requester = User::factory()->create(['role' => 'requester', 'unit_id' => $this->patuha->id]);

    $permohonan = Permohonan::factory()->create([
        'requester_id' => $requester->id,
        'consumerial_tools_id' => $vehicle->id,
        'unit_id' => $this->patuha->id,
    ]);

    // buat bukti LALU kosongkan unit_id-nya (simulasi baris lama)
    $proof = Transaction_Proofs::create([
        'transaction_id' => 'TRX-LEGACY',
        'req_id' => $permohonan->id,
        'requester_id' => $requester->id,
        'voucher_number' => 'VCH-LEGACY',
        'purchase_datetime' => now(),
        'fuel_volume' => 1,
        'struk_bbm_path' => 'x.jpg',
    ]);

    Transaction_Proofs::where('id', $proof->id)->update(['unit_id' => null]);

    // masih tertangkap lewat relasi permohonan
    expect(Transaction_Proofs::inUnit($this->patuha->id)->count())->toBe(1);
});
