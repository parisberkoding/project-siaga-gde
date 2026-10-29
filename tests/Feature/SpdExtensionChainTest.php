<?php

use App\Models\SuratSpd;
use App\Models\SuratSpdExtension;
use App\Models\SuratSpdFundAddition;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * BUG-SPD-001 — flow perpanjangan (extension) dan chain-nya.
 */
beforeEach(function () {
    $this->unit = Unit::create(['code' => 'PTH', 'name' => 'Unit Patuha']);

    $this->driver = User::factory()->create([
        'role' => 'requester',
        'unit_id' => $this->unit->id,
        'nama_lengkap' => 'Driver Uji',
    ]);

    $this->approver = User::factory()->create([
        'role' => 'general_affairs',
        'unit_id' => $this->unit->id,
    ]);

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved' => true,
        'is_active' => true,
    ]);

    $this->original = SuratSpd::factory()->create([
        'user_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'unit_id' => $this->unit->id,
        'status' => 'approved',
        'approved_by' => $this->approver->id,
        'approved_at' => now(),
        'tanggal_berangkat' => now()->toDateString(),
        'tanggal_kembali' => now()->addDays(2)->toDateString(),
        'lpj_deadline' => now()->addDays(5)->endOfDay(),
    ]);

    // Event `creating` menghitung total_budget dari budget_per_hari (kolom
    // legacy yang sudah tidak ada), jadi nilai budget di-set eksplisit.
    $this->original->update(['total_budget' => 1500000]);
    $this->original->refresh();
});

it('membuat perpanjangan dengan unit yang sama dan muncul di queue unit', function () {
    $extension = $this->original->createExtension(
        now()->addDays(4)->toDateString(),
        'Kebutuhan operasional tambahan di lokasi',
        false
    );

    $record = $extension->extensionRecord;

    expect($extension->unit_id)->toBe($this->original->unit_id)
        ->and($extension->parent_surat_id)->toBe($this->original->id)
        ->and($extension->is_extension)->toBeTrue()
        ->and($record)->not->toBeNull()
        ->and($record->original_surat_id)->toBe($this->original->id)
        ->and($record->status)->toBe('pending');

    expect(
        SuratSpdExtension::inUnit($this->unit->id)->pending()
            ->where('extension_surat_id', $extension->id)
            ->exists()
    )->toBeTrue();
});

it('approve perpanjangan memindahkan tanggal kembali, deadline, dan budget ke chain', function () {
    $extension = $this->original->createExtension(
        now()->addDays(4)->toDateString(),
        'Kebutuhan operasional tambahan di lokasi',
        false
    );

    $record = $extension->extensionRecord;
    $budgetTambahan = (float) $record->budget_tambahan;
    $totalAwal = (float) $this->original->fresh()->total_budget;

    // Hanya record pending yang bisa diproses.
    expect($budgetTambahan)->toBe(1000000.0);

    $record->approve($this->approver->id);

    $original = $this->original->fresh();

    expect($original->tanggal_kembali->format('Y-m-d'))->toBe(now()->addDays(4)->format('Y-m-d'))
        ->and($original->lpj_deadline->format('Y-m-d'))->toBe(now()->addDays(7)->format('Y-m-d'))
        ->and((float) $original->total_budget)->toBe($totalAwal + $budgetTambahan)
        ->and((int) $original->extension_count)->toBe(1)
        ->and($extension->fresh()->status)->toBe('approved')
        ->and($record->fresh()->status)->toBe('approved');

    $fundAddition = SuratSpdFundAddition::where('surat_spd_id', $this->original->id)->first();

    expect($fundAddition)->not->toBeNull()
        ->and((float) $fundAddition->nominal_tambahan)->toBe($budgetTambahan)
        ->and($fundAddition->alasan)->toContain('Perpanjangan SPD')
        ->and($fundAddition->created_by)->toBe($this->approver->id);

    // Idempotent: approve dua kali tidak menambah budget/fund addition lagi.
    $record->fresh()->approve($this->approver->id);

    expect((float) $this->original->fresh()->total_budget)->toBe($totalAwal + $budgetTambahan)
        ->and((int) $this->original->fresh()->extension_count)->toBe(1)
        ->and(SuratSpdFundAddition::where('surat_spd_id', $this->original->id)->count())->toBe(1);
});

it('perpanjangan kedua menunjuk ke perpanjangan pertama sebagai parent', function () {
    $extensionPertama = $this->original->createExtension(
        now()->addDays(4)->toDateString(),
        'Kebutuhan operasional tambahan di lokasi',
        false
    );

    $extensionPertama->extensionRecord->approve($this->approver->id);

    $extensionKedua = $extensionPertama->fresh()->createExtension(
        now()->addDays(6)->toDateString(),
        'Perpanjangan kedua karena pekerjaan belum selesai',
        false
    );

    expect($extensionKedua->parent_surat_id)->toBe($extensionPertama->id)
        ->and($extensionKedua->unit_id)->toBe($this->unit->id)
        ->and($extensionKedua->extensionRecord->original_surat_id)->toBe($extensionPertama->id)
        ->and($extensionKedua->getRootSurat()->id)->toBe($this->original->id)
        ->and($extensionKedua->getOriginalSurat()->id)->toBe($this->original->id);

    // Approve extension kedua: root ikut terdorong, budget bertambah lagi.
    $budgetTambahanKedua = (float) $extensionKedua->extensionRecord->budget_tambahan;
    $totalSebelum = (float) $this->original->fresh()->total_budget;

    $extensionKedua->extensionRecord->approve($this->approver->id);

    $original = $this->original->fresh();
    $pertama = $extensionPertama->fresh();

    expect((float) $original->total_budget)->toBe($totalSebelum + $budgetTambahanKedua)
        ->and((int) $original->extension_count)->toBe(2)
        ->and($original->tanggal_kembali->format('Y-m-d'))->toBe(now()->addDays(6)->format('Y-m-d'))
        ->and($pertama->tanggal_kembali->format('Y-m-d'))->toBe(now()->addDays(6)->format('Y-m-d'))
        ->and((int) $pertama->extension_count)->toBe(1)
        ->and($extensionKedua->getAllSuratInChain()->pluck('id')->all())
            ->toContain($this->original->id, $extensionPertama->id, $extensionKedua->id);
});
