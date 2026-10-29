<?php

use App\Models\Approved_Request;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * BUG-BBM-003 (catatan approval tersimpan di voucher), BUG-BBM-004 (approval
 * satuan & massal dibatasi unit), dan BUG-EXP-001 (export voucher dibatasi
 * unit).
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);

    $this->unitPatuha = Unit::where('code', 'PATUHA')->firstOrFail();
    $this->unitDieng = Unit::where('code', 'DIENG')->firstOrFail();

    $this->adminPatuha = User::factory()->create([
        'role'    => 'admin',
        'unit_id' => $this->unitPatuha->id,
    ]);
    $this->adminPatuha->roles()->attach(
        Role::where('code', 'general_affairs')->firstOrFail()->id,
        ['is_primary' => true]
    );

    $this->requesterPatuha = User::factory()->create([
        'role'    => 'requester',
        'unit_id' => $this->unitPatuha->id,
    ]);
    $this->requesterDieng = User::factory()->create([
        'role'    => 'requester',
        'unit_id' => $this->unitDieng->id,
    ]);

    $this->vehiclePatuha = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'Kendaraan',
        'is_approved'      => true,
        'is_active'        => true,
        'unit_id'          => $this->unitPatuha->id,
    ]);
    $this->vehicleDieng = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'Kendaraan',
        'is_approved'      => true,
        'is_active'        => true,
        'unit_id'          => $this->unitDieng->id,
    ]);

    $this->makePending = function (Unit $unit, ?User $requester = null, ?VehicleAndToolsConsumers $vehicle = null): Permohonan {
        return Permohonan::factory()->create([
            'requester_id'         => ($requester ?? $this->requesterPatuha)->id,
            'consumerial_tools_id' => ($vehicle ?? $this->vehiclePatuha)->id,
            'unit_id'              => $unit->id,
            'status'               => 'pending',
            'bill_amounts'         => 300000,
            'request_date'         => now(),
        ]);
    };
});

it('menolak approval satuan untuk permohonan unit lain', function () {
    $foreign = ($this->makePending)($this->unitDieng, $this->requesterDieng, $this->vehicleDieng);

    $this->actingAs($this->adminPatuha)
        ->from(route('admin-approval'))
        ->post(route('admin-process', $foreign->id), [
            'action'           => 'approve',
            'authorizer_notes' => 'Coba lintas unit',
        ])
        ->assertSessionHas('error');

    $fresh = $foreign->fresh();
    expect($fresh->status)->toBe('pending');
    expect($fresh->authorizer_id)->toBeNull();
    expect(Approved_Request::where('permohonan_id', $foreign->id)->exists())->toBeFalse();
});

it('melewati keputusan unit lain pada approval massal', function () {
    $own = ($this->makePending)($this->unitPatuha, $this->requesterPatuha, $this->vehiclePatuha);
    $foreign = ($this->makePending)($this->unitDieng, $this->requesterDieng, $this->vehicleDieng);

    $this->actingAs($this->adminPatuha)
        ->post(route('admin-bulk-process'), [
            'decisions' => json_encode([
                ['request_id' => $foreign->id, 'status' => 'approve', 'note' => 'Lintas unit'],
                ['request_id' => $own->id, 'status' => 'approve', 'note' => 'Unit sendiri'],
            ]),
        ])
        ->assertRedirect(route('admin-dashboard'))
        ->assertSessionHas('success');

    expect(session('success'))->toContain('dilewati');
    expect($foreign->fresh()->status)->toBe('pending');
    expect(Approved_Request::where('permohonan_id', $foreign->id)->exists())->toBeFalse();

    expect($own->fresh()->status)->toBe('approved');
    expect(Approved_Request::where('permohonan_id', $own->id)->exists())->toBeTrue();
});

it('menyimpan catatan approval pada voucher hasil approval satuan', function () {
    $pending = ($this->makePending)($this->unitPatuha, $this->requesterPatuha, $this->vehiclePatuha);

    $this->actingAs($this->adminPatuha)
        ->post(route('admin-process', $pending->id), [
            'action'           => 'approve',
            'authorizer_notes' => 'Catatan uji satuan',
        ])
        ->assertSessionHas('success');

    $voucher = Approved_Request::where('permohonan_id', $pending->id)->first();
    expect($voucher)->not->toBeNull();
    expect($voucher->approval_notes)->toBe('[ADMIN APPROVAL] Catatan uji satuan');

    // Perilaku catatan pada permohonan tidak berubah (catatan mentah).
    expect($pending->fresh()->authorizer_notes)->toBe('Catatan uji satuan');
});

it('menyimpan catatan approval pada voucher hasil approval massal', function () {
    $withNote = ($this->makePending)($this->unitPatuha, $this->requesterPatuha, $this->vehiclePatuha);
    $withoutNote = ($this->makePending)($this->unitPatuha, $this->requesterPatuha, $this->vehiclePatuha);

    $this->actingAs($this->adminPatuha)
        ->post(route('admin-bulk-process'), [
            'decisions' => json_encode([
                ['request_id' => $withNote->id, 'status' => 'approve', 'note' => 'Catatan uji massal'],
                ['request_id' => $withoutNote->id, 'status' => 'approve'],
            ]),
        ])
        ->assertSessionHas('success');

    expect(Approved_Request::where('permohonan_id', $withNote->id)->value('approval_notes'))
        ->toBe('[ADMIN APPROVAL] Catatan uji massal');
    expect(Approved_Request::where('permohonan_id', $withoutNote->id)->value('approval_notes'))
        ->toBeNull();
});

it('export voucher tidak memuat voucher unit lain', function () {
    $permohonanPatuha = ($this->makePending)($this->unitPatuha, $this->requesterPatuha, $this->vehiclePatuha);
    $permohonanDieng = ($this->makePending)($this->unitDieng, $this->requesterDieng, $this->vehicleDieng);

    $date = now()->subDay()->toDateString();

    $voucherPatuha = Approved_Request::create([
        'permohonan_id'   => $permohonanPatuha->id,
        'approval_date'   => now()->subDay(),
        'voucher_code'    => 'VCH-PATUHA-EXPORT',
        'valid_until'     => now()->addDay(),
        'approved_amount' => 300000,
        'authorizer_id'   => $this->adminPatuha->id,
        'status'          => 'approved',
        'unit_id'         => $this->unitPatuha->id,
    ]);
    $voucherDieng = Approved_Request::create([
        'permohonan_id'   => $permohonanDieng->id,
        'approval_date'   => now()->subDay(),
        'voucher_code'    => 'VCH-DIENG-EXPORT',
        'valid_until'     => now()->addDay(),
        'approved_amount' => 300000,
        'authorizer_id'   => $this->adminPatuha->id,
        'status'          => 'approved',
        'unit_id'         => $this->unitDieng->id,
    ]);

    $response = $this->actingAs($this->adminPatuha)
        ->post(route('admin.vouchers.export'), ['date' => $date]);

    $response->assertStatus(200);
    $content = $response->streamedContent();

    $tmp = tempnam(sys_get_temp_dir(), 'vch').'.xlsx';
    file_put_contents($tmp, $content);
    $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getActiveSheet();

    $all = '';
    foreach ($sheet->getRowIterator() as $row) {
        $cells = $row->getCellIterator();
        $cells->setIterateOnlyExistingCells(false);
        foreach ($cells as $cell) {
            $all .= '|'.$cell->getValue();
        }
    }
    @unlink($tmp);

    expect($all)->toContain($voucherPatuha->voucher_code);
    expect($all)->not->toContain($voucherDieng->voucher_code);

    // ID unit lain tidak boleh bocor: export "terpilih" hanya berisi id asing
    // harus ditolak (tidak ada data untuk diexport).
    $this->actingAs($this->adminPatuha)
        ->from(route('admin-dashboard'))
        ->post(route('admin.vouchers.export'), ['ids' => [$voucherDieng->id]])
        ->assertRedirect()
        ->assertSessionHas('error');
});
