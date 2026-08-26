<?php

use App\Exports\DispatcherCheckingExport;
use App\Models\Role;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleChecking;
use App\Models\VehicleCheckingDetail;
use App\Models\VehicleCheckingItemResult;
use App\Models\VehiclePart;
use Carbon\Carbon;
use Database\Seeders\RbacSeeder;
use Database\Seeders\VehiclePartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RbacSeeder::class);
    $this->seed(VehiclePartSeeder::class);

    $this->actor = function (string $roleCode) {
        $user = User::factory()->create(['role' => $roleCode]);
        $role = Role::where('code', $roleCode)->first();
        $user->roles()->attach($role->id, ['is_primary' => true]);

        return $user;
    };

    $this->vehicle = VehicleAndToolsConsumers::factory()->create([
        'is_active' => true,
        'consumerial_type' => 'Kendaraan',
        'consumerial_code' => 'B 1234 XYZ',
        'consumerial_name' => 'Innova',
    ]);
});

it('form export dapat diakses oleh superadmin, dispatcher, dan driver', function () {
    foreach (['superadmin', 'dispatcher', 'requester'] as $role) {
        $user = ($this->actor)($role);
        $this->actingAs($user)
            ->get(route('dispatcher.export-checking.index'))
            ->assertStatus(200)
            ->assertSee('Export Form Pengecekan');
    }
});

it('form export ditolak untuk role tanpa akses', function () {
    $manager = ($this->actor)('manager');
    $this->actingAs($manager)
        ->get(route('dispatcher.export-checking.index'))
        ->assertForbidden();
});

it('mengekspor layout pengecekan ke xlsx untuk superadmin', function () {
    $superadmin = ($this->actor)('superadmin');

    $this->actingAs($superadmin)
        ->post(route('dispatcher.export-checking.download'), [
            'vehicle_id' => $this->vehicle->id,
            'month' => now()->month,
            'year' => now()->year,
        ])
        ->assertStatus(200)
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertHeader('content-disposition', 'attachment; filename=form-pengecekan-B1234XYZ-'.now()->format('Y-m').'.xlsx');
});

it('mengekspor layout pengecekan untuk dispatcher dan driver', function () {
    foreach (['dispatcher', 'requester'] as $role) {
        $user = ($this->actor)($role);
        $this->actingAs($user)
            ->post(route('dispatcher.export-checking.download'), [
                'vehicle_id' => $this->vehicle->id,
                'month' => now()->month,
                'year' => now()->year,
            ])
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
});

it('mendukung kop kustom (nama perusahaan & logo) saat export', function () {
    $superadmin = ($this->actor)('superadmin');
    $logo = UploadedFile::fake()->image('kop-logo.png', 120, 120);

    $this->actingAs($superadmin)
        ->post(route('dispatcher.export-checking.download'), [
            'vehicle_id' => $this->vehicle->id,
            'month' => now()->month,
            'year' => now()->year,
            'company_name' => 'PT. Perusahaan Contoh',
            'unit_name' => 'Divisi Operasional',
            'logo' => $logo,
        ])
        ->assertStatus(200)
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('menolak export tanpa memilih kendaraan', function () {
    $superadmin = ($this->actor)('superadmin');

    $this->actingAs($superadmin)
        ->from(route('dispatcher.export-checking.index'))
        ->post(route('dispatcher.export-checking.download'), [
            'month' => now()->month,
            'year' => now()->year,
        ])
        ->assertRedirect(route('dispatcher.export-checking.index'))
        ->assertSessionHasErrors('vehicle_id');
});

it('menyusun layout harian, mingguan, bulanan, dan tanda tangan', function () {
    $month = Carbon::create(now()->year, now()->month, 1);
    $part = VehiclePart::where('check_frequency', 'daily')->first();

    // Header + detail harian (hari ini) dengan satu item result, satu bulanan, satu mingguan.
    $header = VehicleChecking::create([
        'checking_code' => VehicleChecking::generateCode($this->vehicle->consumerial_code, $month),
        'vehicle_id' => $this->vehicle->id,
        'nopol' => $this->vehicle->consumerial_code,
        'revision' => 0,
        'period_start' => $month->copy()->startOfMonth()->toDateString(),
        'period_end' => $month->copy()->endOfMonth()->toDateString(),
        'generated_by' => User::factory()->create(['role' => 'superadmin'])->id,
        'status' => 'active',
    ]);

    foreach (['daily', 'weekly', 'monthly'] as $freq) {
        $detail = VehicleCheckingDetail::create([
            'checking_detail_code' => VehicleCheckingDetail::generateCode($header),
            'vehicle_checking_id' => $header->id,
            'type' => 'routine',
            'sub_type' => $freq,
            'dispatcher_id' => User::factory()->create(['role' => 'dispatcher'])->id,
            'current_driver_id' => User::factory()->create(['role' => 'requester'])->id,
            'checked_at' => $month->copy()->day(5)->hour(9),
            'driver_acknowledged' => false,
            'notes' => null,
        ]);

        $freqPart = VehiclePart::where('check_frequency', $freq)->orderBy('sort_order')->first();
        VehicleCheckingItemResult::create([
            'vehicle_checking_detail_id' => $detail->id,
            'vehicle_part_id' => $freqPart->id,
            'status' => 'ok',
            'notes' => $freq === 'monthly' ? 'Masih berlaku' : null,
            'needs_replacement' => false,
        ]);
    }

    // Simpan hasil export ke file sementara, lalu baca kembali.
    // `download()` menulis langsung ke response streaming, jadi kita bangun
    // workbook via build() (reflection untuk membaca property privat) lalu save.
    $tmp = tempnam(sys_get_temp_dir(), 'checking-').'.xlsx';
    $export = new DispatcherCheckingExport($this->vehicle, $month, [
        'company_name' => 'PT. Contoh Energi',
    ]);

    $ref = new ReflectionClass($export);
    $build = $ref->getMethod('build');
    $build->setAccessible(true);
    $build->invoke($export);

    $prop = $ref->getProperty('spreadsheet');
    $prop->setAccessible(true);
    $spreadsheet = $prop->getValue($export);
    $writer = new Xlsx($spreadsheet);
    $writer->save($tmp);

    $loaded = IOFactory::load($tmp);
    $sheet = $loaded->getActiveSheet();
    @unlink($tmp);

    // Kop & info
    expect($sheet->getCell('B1')->getValue())->toBe('PT. Contoh Energi');
    expect((string) $sheet->getCell('B3')->getValue())->toContain($this->vehicle->consumerial_code);
    expect((string) $sheet->getCell('B5')->getValue())->toContain($month->translatedFormat('F Y'));

    // Sections
    $values = [
        'PENGECEKAN HARIAN',
        'PENGECEKAN MINGGUAN',
        'PENGECEKAN BULANAN',
        'Tanda Tangan Driver',
        'Tanda Tangan Dispatcher',
    ];

    $allValues = '';
    foreach ($sheet->toArray() as $row) {
        $allValues .= implode('|', array_map(fn ($c) => (string) $c, $row))."\n";
    }
    foreach ($values as $v) {
        expect($allValues)->toContain($v);
    }

    // Data harian: item harian muncul + status V pada kolom hari ke-5 (karena cek tanggal 5).
    $allRows = array_values(array_filter($sheet->toArray(), fn ($r) => (string) $r[1] !== '' && $r[1] !== null));
    $dailyRow = collect($allRows)->first(fn ($r) => $r[1] === $part->name);
    expect($dailyRow)->not->toBeNull();
    // Kolom hari ke-5 = kolom index 6 (A=0, B=1, C=2 + 4 hari) → index 6
    expect($dailyRow[6] ?? null)->toBe('V');

    // Data bulanan: item bulanan + keterangan
    $monthlyPart = VehiclePart::where('check_frequency', 'monthly')->orderBy('sort_order')->first();
    $monthlyRow = collect($allRows)->first(fn ($r) => $r[1] === $monthlyPart->name);
    expect($monthlyRow)->not->toBeNull();
    expect((string) $monthlyRow[4])->toContain('Masih berlaku'); // kolom keterangan (E)
});
