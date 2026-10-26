<?php

use App\Models\Role;
use App\Models\SpdDailyReport;
use App\Models\StrukItem;
use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Services\ImageAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

/**
 * Analyzer palsu: mengembalikan hasil OCR yang sudah ditentukan, dan merekam
 * konteks periode SPD yang dikirim controller.
 */
function fakeAnalyzer(?string $tanggal, string $kategori = 'BBM', float $nominal = 700000): ImageAnalysisService
{
    return new class($tanggal, $kategori, $nominal) extends ImageAnalysisService
    {
        public ?array $lastContext = null;

        public function __construct(
            private ?string $tanggal,
            private string $kategori,
            private float $nominal,
        ) {}

        public function analyzeImage(string $path, array $context = []): array
        {
            $this->lastContext = $context;

            return [
                'status'   => self::STATUS_OK,
                'kategori' => $this->kategori,
                'metadata' => [
                    'tanggal'         => $this->tanggal,
                    'tanggal_mentah'  => $this->tanggal ? 'Waktu 25/06/2026 04:44:54' : null,
                    'nominal'         => $this->nominal,
                    'volume'          => null,
                    'harga_per_liter' => null,
                    'plat_nomor'      => null,
                ],
                'text' => 'STRUK UJI',
            ];
        }
    };
}

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);

    $this->driver = User::factory()->create(['role' => 'requester']);
    $role = Role::where('code', 'requester')->first();
    if ($role) {
        $this->driver->roles()->attach($role->id, ['is_primary' => true]);
    }

    $vehicle = VehicleAndToolsConsumers::factory()->create([
        'consumerial_type' => 'KENDARAAN',
        'is_approved'      => true,
        'is_active'        => true,
    ]);

    // Periode SPD: 6–8 September 2026 (mengikuti kasus nyata di lapangan).
    $this->surat = SuratSpd::factory()->create([
        'user_id'           => $this->driver->id,
        'vehicle_id'        => $vehicle->id,
        'status'            => 'approved',
        'tanggal_berangkat' => '2026-09-06',
        'tanggal_kembali'   => '2026-09-08',
        'lpj_deadline'      => '2026-09-11',
    ]);

    $this->upload = function (ImageAnalysisService $analyzer) {
        $this->app->instance(ImageAnalysisService::class, $analyzer);

        return $this->actingAs($this->driver)
            ->post(route('pelaporan.spd.daily.upload-items', $this->surat), [
                'images' => [UploadedFile::fake()->image('struk.jpg')],
            ]);
    };
});

it('menyisipkan aturan tanggal DD/MM/YYYY dan konteks periode ke prompt OCR', function () {
    $service = new ImageAnalysisService();

    $prompt = $service->buildSystemPrompt([
        'nomor_surat'     => '010.TGS/PTH1.30-GDE/IX/2026',
        'periode_mulai'   => '2026-09-06',
        'periode_selesai' => '2026-09-08',
    ]);

    // Aturan kunci yang mencegah salah baca hari & tahun.
    expect($prompt)->toContain('HARI-BULAN-TAHUN')
        ->toContain('DD/MM/YYYY')
        ->toContain('25/06/2026')
        ->toContain('tanggal_mentah')
        ->toContain('menggeser hari (±1)')
        ->toContain('JANGAN mengubah tahun')
        ->toContain('2025')
        // Konteks periode dipakai untuk verifikasi silang.
        ->toContain('KONTEKS PERJALANAN DINAS')
        ->toContain('010.TGS/PTH1.30-GDE/IX/2026')
        ->toContain('6 September 2026')
        ->toContain('8 September 2026');

    // Tanpa konteks, aturan tanggal tetap ada tapi blok konteks tidak.
    $tanpaKonteks = $service->buildSystemPrompt();
    expect($tanpaKonteks)->toContain('ATURAN TANGGAL')
        ->not->toContain('KONTEKS PERJALANAN DINAS');
});

it('membaca tanggal "25/06/2026" apa adanya (tidak bergeser jadi 26 dan tidak jadi 2025)', function () {
    $analyzer = fakeAnalyzer('25/06/2026');
    ($this->upload)($analyzer)->assertOk()->assertJson(['success' => true]);

    $item = StrukItem::where('spd_id', $this->surat->id)->firstOrFail();

    expect($item->tanggal->format('Y-m-d'))->toBe('2026-06-25');
});

it('tetap benar saat model mengembalikan tanggal lengkap dengan jam', function () {
    $analyzer = fakeAnalyzer('25/06/2026 04:44:54');
    ($this->upload)($analyzer)->assertOk();

    $item = StrukItem::where('spd_id', $this->surat->id)->firstOrFail();

    expect($item->tanggal->format('Y-m-d'))->toBe('2026-06-25');
});

it('mengirim konteks periode SPD ke analyzer OCR', function () {
    $analyzer = fakeAnalyzer('2026-09-07');
    ($this->upload)($analyzer)->assertOk();

    expect($analyzer->lastContext)
        ->toMatchArray([
            'nomor_surat'     => $this->surat->nomor_surat,
            'periode_mulai'   => '2026-09-06',
            'periode_selesai' => '2026-09-08',
        ]);
});

it('menandai tanggal di luar periode SPD tanpa menolak datanya', function () {
    // Kasus nyata: struk 26 Juni 2025 padahal periode SPD September 2026.
    $analyzer = fakeAnalyzer('2025-06-26', 'BBM', 1300000);
    ($this->upload)($analyzer)->assertOk()->assertJson(['success' => true]);

    $item = StrukItem::where('spd_id', $this->surat->id)->firstOrFail();
    $meta = $item->metadata_json;

    // Data tetap tersimpan (tidak diblokir) — hanya ditandai.
    expect($item->tanggal->format('Y-m-d'))->toBe('2025-06-26')
        ->and($meta['tanggal_di_luar_periode'])->toBeTrue()
        ->and($meta['tanggal_tahun_beda'])->toBeTrue()
        ->and($meta['tanggal_periode_spd'])->toBe('2026-09-06 s/d 2026-09-08')
        ->and($meta['tanggal_mentah'])->toBe('Waktu 25/06/2026 04:44:54');
});

it('tidak menandai tanggal yang masih di dalam periode SPD', function () {
    $analyzer = fakeAnalyzer('2026-09-07');
    ($this->upload)($analyzer)->assertOk();

    $item = StrukItem::where('spd_id', $this->surat->id)->firstOrFail();

    expect($item->metadata_json['tanggal_di_luar_periode'])->toBeFalse()
        ->and($item->metadata_json['tanggal_tahun_beda'])->toBeFalse();
});

it('menampilkan badge "Tanggal perlu dicek" pada item yang tanggalnya di luar periode', function () {
    $analyzer = fakeAnalyzer('2025-06-26');
    ($this->upload)($analyzer)->assertOk();

    $this->actingAs($this->driver)
        ->get(route('pelaporan.spd.validasi', $this->surat))
        ->assertStatus(200)
        ->assertSee('Tanggal perlu dicek', false);
});

it('memakai tanggal laporan sebagai fallback bila tanggal struk tidak terbaca', function () {
    $analyzer = fakeAnalyzer(null);
    ($this->upload)($analyzer)->assertOk();

    $item = StrukItem::where('spd_id', $this->surat->id)->firstOrFail();
    $daily = SpdDailyReport::find($item->spd_daily_report_id);

    expect($item->tanggal->format('Y-m-d'))->toBe($daily->tanggal->format('Y-m-d'));
});

it('TIDAK mengubah tanggal ISO yang benar menjadi 26 Juni 2025 (bug parser lama)', function () {
    // Kasus nyata dari lapangan. Struk SPBU tertulis "Waktu 25/06/2026 04:44:54".
    // Model mengembalikan ISO yang BENAR "2026-06-25", tetapi parser lama
    // mencocokkan ekor string "26-06-25" sebagai tanggal tahun-pendek lalu
    // menulisnya ulang menjadi 26-06-2025 → tampil "Kamis, 26 Juni 2025"
    // (hari bergeser 25→26 DAN tahun bergeser 2026→2025 sekaligus).
    $controller = app(\App\Http\Controllers\SpdDailyReportController::class);
    $refleksi = new \ReflectionMethod($controller, 'parseAiTanggal');
    $refleksi->setAccessible(true);

    $hasil = $refleksi->invoke($controller, '2026-06-25');

    expect($hasil)->toBe('2026-06-25')
        ->and($hasil)->not->toBe('2025-06-26');

    // End-to-end: item yang tersimpan harus memakai 25 Juni 2026.
    $analyzer = fakeAnalyzer('2026-06-25');
    ($this->upload)($analyzer)->assertOk();

    $item = StrukItem::where('spd_id', $this->surat->id)->firstOrFail();

    expect($item->tanggal->format('Y-m-d'))->toBe('2026-06-25')
        ->and($item->tanggal->translatedFormat('d F Y'))->toBe('25 Juni 2026')
        // item_code ikut memakai tanggal yang benar (d-m-Y).
        ->and($item->item_code)->toContain('25-06-2026')
        ->and($item->item_code)->not->toContain('26-06-2025');
});

it('menormalkan berbagai format tanggal struk tanpa menggeser hari/bulan/tahun', function () {
    $controller = app(\App\Http\Controllers\SpdDailyReportController::class);
    $refleksi = new \ReflectionMethod($controller, 'parseAiTanggal');
    $refleksi->setAccessible(true);
    $parse = fn (string $v) => $refleksi->invoke($controller, $v);

    // Kasus paling krusial: ISO yang tahunnya 4 digit TIDAK boleh "diperbaiki"
    // menjadi tahun lain (bug lama: 2025-06-26 → 2026-06-25).
    expect($parse('2025-06-26'))->toBe('2025-06-26')
        ->and($parse('2026-06-25'))->toBe('2026-06-25')
        ->and($parse('2026-09-07'))->toBe('2026-09-07');

    // Struk Indonesia: hari dulu (DD/MM/YYYY).
    expect($parse('25/06/2026'))->toBe('2026-06-25')
        ->and($parse('25/06/2026 04:44:54'))->toBe('2026-06-25')
        ->and($parse('25-06-2026'))->toBe('2026-06-25')
        ->and($parse('25-6-26'))->toBe('2026-06-25')
        ->and($parse('26 Juni 2025'))->toBe('2025-06-26')
        ->and($parse('5 Agustus 2026'))->toBe('2026-08-05');

    // Tidak terbaca → null (jangan menebak, jangan pakai hari ini).
    expect($parse(''))->toBeNull()
        ->and($parse('tidak terbaca'))->toBeNull();
});
