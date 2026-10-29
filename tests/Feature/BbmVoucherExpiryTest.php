<?php

use App\Models\Approved_Request;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\Transaction_Proofs;
use App\Models\User;
use App\Models\UserSlotAdjustment;
use App\Models\VehicleAndToolsConsumers;
use App\Services\VoucherExpiryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * BUG-BBM-001 — voucher kedaluwarsa tidak boleh dipakai untuk melaporkan
 * pembelian, dan expiry harus dapat dijalankan idempotent + terjadwal.
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

    $this->makeRequestWithVoucher = function (string $voucherStatus, $validUntil): array {
        $permohonan = Permohonan::factory()->create([
            'requester_id'         => $this->requester->id,
            'consumerial_tools_id' => $this->vehicle->id,
            'status'               => 'approved',
            'bill_amounts'         => 300000,
        ]);

        $voucher = Approved_Request::create([
            'permohonan_id'   => $permohonan->id,
            'approval_date'   => now()->subHours(60),
            'voucher_code'    => 'BBMGDE-'.strtoupper(substr(md5(uniqid()), 0, 6)).'-'.now()->format('dmy'),
            'valid_until'     => $validUntil,
            'approved_amount' => 300000,
            'authorizer_id'   => $this->requester->id,
            'status'          => $voucherStatus,
        ]);

        return [$permohonan, $voucher];
    };
});

it('menolak laporan pembelian untuk voucher yang sudah kedaluwarsa', function () {
    [$permohonan, $voucher] = ($this->makeRequestWithVoucher)('approved', now()->subHours(2));

    $this->actingAs($this->requester)
        ->from(route('requester-index'))
        ->post(route('requester.submit-report', $permohonan->id), [
            'purchase_date' => now()->format('Y-m-d'),
            'volume_bbm'    => 20,
            'km_akhir'      => 12000,
            'foto_struk'    => UploadedFile::fake()->image('struk.jpg'),
            'foto_km'       => UploadedFile::fake()->image('odometer.jpg'),
        ])
        ->assertRedirect(route('requester-index'))
        ->assertSessionHas('error');

    expect(session('error'))->toContain('kedaluwarsa');
    expect(Transaction_Proofs::count())->toBe(0);
    expect($permohonan->fresh()->status)->toBe('approved');
    expect($voucher->fresh()->status)->toBe('approved');
});

it('menolak upload bukti untuk voucher yang sudah kedaluwarsa', function () {
    [$permohonan] = ($this->makeRequestWithVoucher)('active', now()->subMinute());

    $this->actingAs($this->requester)
        ->from(route('requester-index'))
        ->post(route('requester-upload-proof', $permohonan->id), [
            'purchase_datetime' => now()->format('Y-m-d H:i:s'),
            'fuel_volume'       => 20,
            'km_terakhir'       => 12000,
            'struk_bbm'         => UploadedFile::fake()->image('struk.jpg'),
        ])
        ->assertRedirect(route('requester-index'))
        ->assertSessionHas('error');

    expect(session('error'))->toContain('kedaluwarsa');
    expect(Transaction_Proofs::count())->toBe(0);
    expect($permohonan->fresh()->status)->toBe('approved');
});

it('menolak laporan pembelian bila voucher tidak ditemukan', function () {
    $permohonan = Permohonan::factory()->create([
        'requester_id'         => $this->requester->id,
        'consumerial_tools_id' => $this->vehicle->id,
        'status'               => 'approved',
        'bill_amounts'         => 300000,
    ]);

    $this->actingAs($this->requester)
        ->from(route('requester-index'))
        ->post(route('requester.submit-report', $permohonan->id), [
            'purchase_date' => now()->format('Y-m-d'),
            'volume_bbm'    => 20,
            'km_akhir'      => 12000,
            'foto_struk'    => UploadedFile::fake()->image('struk.jpg'),
            'foto_km'       => UploadedFile::fake()->image('odometer.jpg'),
        ])
        ->assertRedirect(route('requester-index'))
        ->assertSessionHas('error');

    expect(session('error'))->toContain('Voucher tidak ditemukan');
    expect(Transaction_Proofs::count())->toBe(0);
    expect($permohonan->fresh()->status)->toBe('approved');
});

it('menerima laporan pembelian untuk voucher yang masih berlaku', function () {
    [$permohonan] = ($this->makeRequestWithVoucher)('active', now()->addHours(10));

    $this->actingAs($this->requester)
        ->post(route('requester.submit-report', $permohonan->id), [
            'purchase_date' => now()->format('Y-m-d'),
            'volume_bbm'    => 20,
            'km_akhir'      => 12000,
            'foto_struk'    => UploadedFile::fake()->image('struk.jpg'),
            'foto_km'       => UploadedFile::fake()->image('odometer.jpg'),
        ])
        ->assertSessionHas('success');

    expect($permohonan->fresh()->status)->toBe('completed');
    expect(Transaction_Proofs::where('req_id', $permohonan->id)->count())->toBe(1);
});

it('meng-expire voucher secara idempotent lewat VoucherExpiryService', function () {
    [$permohonan, $voucher] = ($this->makeRequestWithVoucher)('approved', now()->subHour());

    $service = app(VoucherExpiryService::class);

    expect($service->expireStale())->toBe(1);
    expect($voucher->fresh()->status)->toBe('cancelled');

    $fresh = $permohonan->fresh();
    expect($fresh->status)->toBe('cancelled');
    expect($fresh->requester_notes)->toContain('[SYSTEM AUTO-CANCEL]');
    expect(UserSlotAdjustment::where('related_permohonan_id', $permohonan->id)
        ->where('reason', 'voucher_expired')->count())->toBe(1);

    // Pemanggilan kedua tidak mengubah apa pun (idempotent).
    expect($service->expireStale())->toBe(0);
    expect(UserSlotAdjustment::where('related_permohonan_id', $permohonan->id)
        ->where('reason', 'voucher_expired')->count())->toBe(1);
});

it('tidak meng-expire voucher yang masih berlaku', function () {
    [$permohonan, $voucher] = ($this->makeRequestWithVoucher)('active', now()->addHours(1));

    expect(app(VoucherExpiryService::class)->expireStale())->toBe(0);
    expect($voucher->fresh()->status)->toBe('active');
    expect($permohonan->fresh()->status)->toBe('approved');
});

it('mendaftarkan jadwal bbm-expire-vouchers setiap 10 menit', function () {
    $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());

    $event = $events->first(fn ($e) => $e->description === 'bbm-expire-vouchers');

    expect($event)->not->toBeNull();
    expect($event->expression)->toBe('*/10 * * * *');
});
