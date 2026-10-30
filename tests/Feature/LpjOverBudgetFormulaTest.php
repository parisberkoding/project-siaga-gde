<?php

use App\Models\LpjPenggantian;
use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\SuratSpdBudget;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->user = User::factory()->create(['role' => 'requester']);
    $role = Role::where('code', 'requester')->first();
    $this->user->roles()->attach($role->id, ['is_primary' => true]);

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);
});

/**
 * Buat surat 3 hari (1–3 Juni) + satu baris pencairan.
 */
function buatSuratDenganPencairan(User $user, VehicleAndToolsConsumers $vehicle, float $dana): SuratSpd
{
    $surat = SuratSpd::factory()->create([
        'user_id'            => $user->id,
        'vehicle_id'         => $vehicle->id,
        'tanggal_berangkat'  => '2026-06-01',
        'tanggal_kembali'    => '2026-06-03',
        'lpj_deadline'       => '2026-06-06',
        'status'             => 'approved',
    ]);

    SuratSpdBudget::create([
        'surat_spd_id'     => $surat->id,
        'tanggal'          => '2026-06-01',
        'nominal'          => $dana,
        'bentuk_pencairan' => 'Tunai',
        'urutan'           => 1,
    ]);

    return $surat->fresh();
}

it('menghitung budget harian dari total dana dibagi lama hari perjalanan', function () {
    // Satu baris pencairan Rp 900.000 untuk perjalanan 3 hari.
    $surat = buatSuratDenganPencairan($this->user, $this->vehicle, 900000);

    expect((int) $surat->lama_perjalanan_hari)->toBe(3);

    $this->actingAs($this->user)
        ->post(route('requester.lpj.store'), [
            'surat_spd_id'     => $surat->id,
            'nama'             => 'Driver Uji',
            'jabatan'          => 'Driver',
            'uraian'           => 'Uji formula over budget',
            'jumlah_diberikan' => 900000,
            'items'            => [[
                'tanggal'        => '2026-06-01',
                'struk_kategori' => 'BBM',
                'nominal'        => 1200000,
                'keterangan'     => 'BBM perjalanan',
            ]],
        ])
        ->assertRedirect(route('requester.lpj.index'));

    $lpj = LpjPenggantian::where('surat_spd_id', $surat->id)->firstOrFail();

    // Rp 900.000 / 3 hari = Rp 300.000 per hari (BUKAN Rp 900.000 karena 1 baris).
    expect((float) $lpj->budget_per_hari)->toBe(300000.0);
    expect($lpj->is_over_budget)->toBeTrue();
});

it('tidak menandai over budget bila pengeluaran masih di bawah plafon', function () {
    $surat = buatSuratDenganPencairan($this->user, $this->vehicle, 900000);

    $this->actingAs($this->user)
        ->post(route('requester.lpj.store'), [
            'surat_spd_id'     => $surat->id,
            'nama'             => 'Driver Uji',
            'jabatan'          => 'Driver',
            'uraian'           => 'Uji di bawah plafon',
            'jumlah_diberikan' => 900000,
            'items'            => [[
                'tanggal'        => '2026-06-02',
                'struk_kategori' => 'BBM',
                'nominal'        => 600000,
                'keterangan'     => 'BBM perjalanan',
            ]],
        ])
        ->assertRedirect(route('requester.lpj.index'));

    $lpj = LpjPenggantian::where('surat_spd_id', $surat->id)->firstOrFail();

    expect((float) $lpj->budget_per_hari)->toBe(300000.0);
    expect($lpj->is_over_budget)->toBeFalse();
});

it('tidak menandai over budget bila surat belum punya dana/pencairan', function () {
    $surat = SuratSpd::factory()->create([
        'user_id'           => $this->user->id,
        'vehicle_id'        => $this->vehicle->id,
        'tanggal_berangkat' => '2026-06-01',
        'tanggal_kembali'   => '2026-06-03',
        'lpj_deadline'      => '2026-06-06',
        'status'            => 'approved',
    ]);

    $this->actingAs($this->user)
        ->post(route('requester.lpj.store'), [
            'surat_spd_id' => $surat->id,
            'nama'         => 'Driver Uji',
            'jabatan'      => 'Driver',
            'uraian'       => 'Tanpa dana',
            'items'        => [[
                'tanggal'        => '2026-06-01',
                'struk_kategori' => 'BBM',
                'nominal'        => 500000,
                'keterangan'     => 'BBM perjalanan',
            ]],
        ])
        ->assertRedirect(route('requester.lpj.index'));

    $lpj = LpjPenggantian::where('surat_spd_id', $surat->id)->firstOrFail();

    expect((float) $lpj->budget_per_hari)->toBe(0.0);
    expect($lpj->is_over_budget)->toBeFalse();
});
