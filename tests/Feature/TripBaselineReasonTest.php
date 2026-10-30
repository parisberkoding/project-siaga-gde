<?php

use App\Models\Permohonan;
use App\Models\Role;
use App\Models\Transaction_Proofs;
use App\Models\TripValidation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * BUG-EFF-001 — mengubah patokan efisiensi BBM wajib disertai alasan
 * (audit trail) dan flash message yang menyebut alasan tersebut.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->superadmin->roles()->attach(
        Role::where('code', 'superadmin')->firstOrFail()->id,
        ['is_primary' => true]
    );

    $requester = User::factory()->create(['role' => 'requester']);

    $permohonan = Permohonan::factory()->create([
        'requester_id' => $requester->id,
        'status' => 'completed',
        'gasoline_type' => 'Pertamax',
        'bill_amounts' => 300000,
    ]);

    $prev = Transaction_Proofs::create([
        'transaction_id' => 'TRX-EFF-PREV',
        'req_id' => $permohonan->id,
        'requester_id' => $requester->id,
        'purchase_datetime' => now()->subDays(5),
        'fuel_volume' => 40,
        'struk_bbm_path' => 'struk/uji-prev.jpg',
    ]);

    $latest = Transaction_Proofs::create([
        'transaction_id' => 'TRX-EFF-LATEST',
        'req_id' => $permohonan->id,
        'requester_id' => $requester->id,
        'purchase_datetime' => now()->subDay(),
        'fuel_volume' => 40,
        'struk_bbm_path' => 'struk/uji-latest.jpg',
    ]);

    $this->trip = TripValidation::create([
        'transaction_proof_previous_id' => $prev->id,
        'transaction_proof_latest_id' => $latest->id,
        'previous_odo' => 1000,
        'latest_odo' => 1500,
        'diff_odo' => 500,
        'fuel_type' => 'Pertamax',
        'fuel_volume' => 40,
    ]);
});

it('menolak perubahan patokan BBM tanpa alasan', function () {
    $this->actingAs($this->superadmin)
        ->from(route('superadmin.trip-analysis.index'))
        ->post(route('superadmin.trip-validations.validate', $this->trip->id), [
            'is_long_trip' => 0,
            'baseline_override' => 18,
        ])
        ->assertSessionHasErrors('baseline_override_reason');

    expect($this->trip->fresh()->baseline_override)->toBeNull()
        ->and($this->trip->fresh()->baseline_override_reason)->toBeNull();
});

it('menyimpan patokan + alasan dan menampilkan flash message audit', function () {
    $this->actingAs($this->superadmin)
        ->post(route('superadmin.trip-validations.validate', $this->trip->id), [
            'is_long_trip' => 1,
            'baseline_override' => 18,
            'baseline_override_reason' => 'Jalan pegunungan dan beban penuh',
        ])
        ->assertSessionHas('success');

    $trip = $this->trip->fresh();

    expect((float) $trip->baseline_override)->toBe(18.0)
        ->and($trip->baseline_override_reason)->toBe('Jalan pegunungan dan beban penuh')
        ->and($trip->baseline_overridden_by)->toBe($this->superadmin->id)
        ->and($trip->baseline_overridden_at)->not->toBeNull()
        ->and(session('success'))->toContain('Alasan perubahan tercatat');
});

it('tetap mengizinkan validasi tanpa perubahan patokan', function () {
    $this->actingAs($this->superadmin)
        ->post(route('superadmin.trip-validations.validate', $this->trip->id), [
            'is_long_trip' => 0,
        ])
        ->assertSessionHas('success')
        ->assertSessionMissing('error');

    expect($this->trip->fresh()->baseline_override)->toBeNull();
});
