<?php

use App\Models\Approved_Request;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\Transaction_Proofs;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * BUG-BBM-002 — isian manual (tanpa metadata AI) harus sampai ke server dan
 * lolos validasi nominal, baik lewat payload parsial maupun tanpa array hidden.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    Storage::fake('public');

    $this->requester = User::factory()->create(['role' => 'requester']);
    $role = Role::where('code', 'requester')->first();
    $this->requester->roles()->attach($role->id, ['is_primary' => true]);

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'Kendaraan',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    $this->permohonan = Permohonan::factory()->create([
        'requester_id'         => $this->requester->id,
        'consumerial_tools_id' => $this->vehicle->id,
        'status'               => 'approved',
        'bill_amounts'         => 300000,
    ]);

    // Voucher masih berlaku (guard BUG-BBM-001).
    Approved_Request::create([
        'permohonan_id'   => $this->permohonan->id,
        'approval_date'   => now(),
        'voucher_code'    => 'BBMGDE-MANUAL-'.now()->format('dmy'),
        'valid_until'     => now()->addHours(20),
        'approved_amount' => 300000,
        'authorizer_id'   => $this->requester->id,
        'status'          => 'active',
    ]);
});

it('menerima laporan operasional dari nominal manual tanpa metadata AI', function () {
    $this->actingAs($this->requester)
        ->post(route('pelaporan.operational.store'), [
            'permohonan_id' => $this->permohonan->id,
            'purchase_date' => now()->format('Y-m-d'),
            'volume_bbm'    => 20,
            'nominal'       => 300000,
            'foto_struk'    => UploadedFile::fake()->image('struk.jpg'),
            // Sengaja tanpa struk_kategoris / nominals / metadata_s.
        ])
        ->assertSessionHas('success');

    expect($this->permohonan->fresh()->status)->toBe('completed');
    expect(Transaction_Proofs::where('req_id', $this->permohonan->id)->count())->toBe(1);
});

it('menerima laporan saat hidden input basi berisi kosong tetapi nominal utama terisi', function () {
    $this->actingAs($this->requester)
        ->post(route('pelaporan.operational.store'), [
            'permohonan_id' => $this->permohonan->id,
            'purchase_date' => now()->format('Y-m-d'),
            'volume_bbm'    => 20,
            'nominal'       => 300000,
            'foto_struk'    => UploadedFile::fake()->image('struk.jpg'),
            // Bentuk payload lama: hidden input belum tersinkron dengan isian manual.
            'struk_kategoris' => [0 => ''],
            'nominals'        => [0 => ''],
            'metadata_s'      => [0 => '{}'],
        ])
        ->assertSessionHas('success');

    expect($this->permohonan->fresh()->status)->toBe('completed');
});

it('menolak laporan operasional bila voucher hilang pada permohonan yang sudah disetujui admin', function () {
    // Permohonan dengan jejak approval tetapi baris voucher hilang = anomali.
    Approved_Request::where('permohonan_id', $this->permohonan->id)->delete();
    $this->permohonan->update([
        'authorizer_id'      => $this->requester->id,
        'authorization_date' => now(),
    ]);

    $this->actingAs($this->requester)
        ->from(route('pelaporan.index'))
        ->post(route('pelaporan.operational.store'), [
            'permohonan_id' => $this->permohonan->id,
            'purchase_date' => now()->format('Y-m-d'),
            'volume_bbm'    => 20,
            'nominal'       => 300000,
            'foto_struk'    => UploadedFile::fake()->image('struk.jpg'),
        ])
        ->assertSessionHas('error');

    expect(session('error'))->toContain('Voucher tidak ditemukan');
    expect($this->permohonan->fresh()->status)->toBe('approved');
    expect(Transaction_Proofs::where('req_id', $this->permohonan->id)->count())->toBe(0);
});

it('tetap menolak nominal manual yang tidak sesuai permohonan', function () {
    $this->actingAs($this->requester)
        ->from(route('pelaporan.index'))
        ->post(route('pelaporan.operational.store'), [
            'permohonan_id' => $this->permohonan->id,
            'purchase_date' => now()->format('Y-m-d'),
            'volume_bbm'    => 20,
            'nominal'       => 150000,
            'foto_struk'    => UploadedFile::fake()->image('struk.jpg'),
        ])
        ->assertSessionHas('error');

    expect($this->permohonan->fresh()->status)->toBe('approved');
    expect(Transaction_Proofs::where('req_id', $this->permohonan->id)->count())->toBe(0);
});
