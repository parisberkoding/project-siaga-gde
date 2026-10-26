<?php

use App\Models\Role;
use App\Models\SuratSpd;
use App\Models\SuratSpdBudget;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->ga = User::factory()->create(['role' => 'superadmin']);
    $role = Role::where('code', 'superadmin')->first();
    $this->ga->roles()->attach($role->id, ['is_primary' => true]);

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'is_active'        => true,
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
    ]);

    $this->payloadDasar = [
        'pemberi_tugas_level'   => 'manager',
        'pemberi_tugas_nama'    => 'Budi Manager',
        'pemberi_tugas_jabatan' => 'Operations Manager',
        'pengemudi_nama'        => 'Pengemudi Uji',
        'tempat_tujuan'         => 'Bandung',
        'melayani_bidang'       => 'Operasional',
        'jumlah_pemakai'        => 1,
        'vehicle_id'            => $this->vehicle->id,
        'tanggal_berangkat'     => now()->addDay()->format('Y-m-d'),
        'tanggal_kembali'       => now()->addDays(3)->format('Y-m-d'),
        'type'                  => 'biasa',
    ];
});

it('membuat surat shortcut dengan pemberian dana per transfer (bukan per hari)', function () {
    $this->actingAs($this->ga)
        ->post(route('surat.superadmin.store-shortcut'), array_merge($this->payloadDasar, [
            'pencairans' => [
                [
                    'tanggal'          => now()->addDay()->format('Y-m-d'),
                    'nominal'          => 1000000,
                    'bentuk_pencairan' => 'Transfer',
                    'bank_reference'   => 'TRF/2026/10/001',
                    'rek_tujuan'       => 'BCA 1234567890 a.n. Pengemudi',
                    'catatan'          => 'uang muka',
                ],
                [
                    'tanggal'          => now()->addDays(2)->format('Y-m-d'),
                    'nominal'          => 500000,
                    'bentuk_pencairan' => 'Tunai',
                    'catatan'          => 'tambahan tunai di lokasi',
                ],
            ],
        ]))
        ->assertRedirect();

    $surat = SuratSpd::latest('id')->firstOrFail();
    $rows = SuratSpdBudget::where('surat_spd_id', $surat->id)->orderBy('urutan')->get();

    expect($rows)->toHaveCount(2)
        // Baris 1 — transfer lengkap dengan referensi & rekening tujuan.
        ->and($rows[0]->bentuk_pencairan)->toBe('Transfer')
        ->and($rows[0]->bank_reference)->toBe('TRF/2026/10/001')
        ->and($rows[0]->rek_tujuan)->toBe('BCA 1234567890 a.n. Pengemudi')
        ->and($rows[0]->catatan)->toBe('uang muka')
        ->and($rows[0]->urutan)->toBe(1)
        // Baris 2 — tunai: referensi/rekening WAJIB dikosongkan.
        ->and($rows[1]->bentuk_pencairan)->toBe('Tunai')
        ->and($rows[1]->bank_reference)->toBeNull()
        ->and($rows[1]->rek_tujuan)->toBeNull()
        ->and($rows[1]->urutan)->toBe(2);

    // Total budget & uang diberikan mengikuti akumulasi pencairan.
    expect((float) $surat->total_budget)->toBe(1500000.0)
        ->and((float) $surat->uang_diberikan)->toBe(1500000.0);
});

it('tetap menerima payload budgets lama (kompatibilitas form)', function () {
    $this->actingAs($this->ga)
        ->post(route('surat.superadmin.store-shortcut'), array_merge($this->payloadDasar, [
            'budgets' => [
                ['tanggal' => now()->addDay()->format('Y-m-d'), 'nominal' => 300000],
            ],
        ]))
        ->assertRedirect();

    $surat = SuratSpd::latest('id')->firstOrFail();

    expect(SuratSpdBudget::where('surat_spd_id', $surat->id)->count())->toBe(1)
        ->and((float) $surat->total_budget)->toBe(300000.0);
});

it('mencatat pemberian dana saat GA memvalidasi (approve) surat', function () {
    // Surat yang menunggu validasi.
    $surat = SuratSpd::factory()->create([
        'user_id'           => $this->ga->id,
        'vehicle_id'        => $this->vehicle->id,
        'status'            => 'pending',
        'tanggal_berangkat' => now()->addDay(),
        'tanggal_kembali'   => now()->addDays(2),
    ]);

    $this->actingAs($this->ga)
        ->post(route('surat.superadmin.approve', $surat), [
            'uang_diberikan' => 1200000,
            'pencairans' => [
                [
                    'tanggal'          => now()->addDay()->format('Y-m-d'),
                    'nominal'          => 1200000,
                    'bentuk_pencairan' => 'Transfer',
                    'bank_reference'   => 'TRF/2026/10/777',
                    'rek_tujuan'       => 'Mandiri 99887766',
                ],
            ],
        ])
        ->assertRedirect();

    $surat->refresh();
    $row = SuratSpdBudget::where('surat_spd_id', $surat->id)->first();

    expect($surat->status)->toBe('approved')
        ->and($row)->not->toBeNull()
        ->and($row->bentuk_pencairan)->toBe('Transfer')
        ->and($row->bank_reference)->toBe('TRF/2026/10/777')
        ->and((float) $surat->total_budget)->toBe(1200000.0)
        ->and((float) $surat->uang_diberikan)->toBe(1200000.0);
});

it('menambah pemberian dana berikutnya sebagai baris baru saat validasi', function () {
    $surat = SuratSpd::factory()->create([
        'user_id'           => $this->ga->id,
        'vehicle_id'        => $this->vehicle->id,
        'status'            => 'pending',
        'tanggal_berangkat' => now()->addDay(),
        'tanggal_kembali'   => now()->addDays(2),
    ]);

    // Pemberian pertama (mis. dari pembuatan surat sebelumnya).
    SuratSpdBudget::create([
        'surat_spd_id'     => $surat->id,
        'tanggal'          => now()->addDay()->format('Y-m-d'),
        'nominal'          => 400000,
        'bentuk_pencairan' => 'Tunai',
        'urutan'           => 1,
    ]);

    $this->actingAs($this->ga)
        ->post(route('surat.superadmin.approve', $surat), [
            'pencairans' => [
                [
                    'tanggal'          => now()->addDays(2)->format('Y-m-d'),
                    'nominal'          => 600000,
                    'bentuk_pencairan' => 'Transfer',
                    'bank_reference'   => 'TRF/002',
                    'rek_tujuan'       => 'BNI 111',
                ],
            ],
        ])
        ->assertRedirect();

    $rows = SuratSpdBudget::where('surat_spd_id', $surat->id)->orderBy('urutan')->get();

    expect($rows)->toHaveCount(2)
        ->and($rows[1]->urutan)->toBe(2)
        ->and((float) $surat->fresh()->total_budget)->toBe(1000000.0);
});

it('menampilkan input pemberian dana di halaman validasi & form shortcut', function () {
    $surat = SuratSpd::factory()->create([
        'user_id'           => $this->ga->id,
        'vehicle_id'        => $this->vehicle->id,
        'status'            => 'pending',
        'tanggal_berangkat' => now()->addDay(),
        'tanggal_kembali'   => now()->addDays(2),
    ]);

    $this->actingAs($this->ga)
        ->get(route('surat.superadmin.show', $surat))
        ->assertStatus(200)
        ->assertSee('Pemberian Dana (Pencairan)', false)
        ->assertSee('Tambah Pemberian Dana', false)
        ->assertSee('Bentuk', false);

    // Form shortcut: label lama "Budget Per Hari" hilang, diganti Pemberian Dana.
    $this->actingAs($this->ga)
        ->get(route('surat.superadmin.create-shortcut'))
        ->assertStatus(200)
        ->assertSee('Pemberian Dana', false)
        ->assertSee('Tambah Pemberian Dana', false)
        ->assertDontSee('Budget Per Hari', false)
        ->assertDontSee('Salin ke semua', false);
});
