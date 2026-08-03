<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SuratSpd;
use App\Models\SuratSpdDetail;
use App\Models\SuratSpdBudget;
use App\Models\SpdBarcodeSignature;
use App\Models\VehicleAndToolsConsumers;
use App\Models\User;
use Carbon\Carbon;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class SuratSpdWithQrSeeder extends Seeder
{
    private const QR_SIZE   = 300;
    private const LOGO_SIZE = 54; // ~18% dari QR

    public function run(): void
    {
        // -------------------------------------------------------
        // 1. Ambil data pendukung
        // -------------------------------------------------------
        $vehicle = VehicleAndToolsConsumers::where('consumerial_type', 'Kendaraan')
            ->where('is_active', true)
            ->first();

        if (!$vehicle) {
            $this->command->error('Tidak ada kendaraan aktif.');
            return;
        }

        $requester = User::where('role', 'requester')->first() ?? User::first();
        $approver  = User::whereIn('role', ['superadmin', 'admin'])->first() ?? User::first();

        if (!$requester || !$approver) {
            $this->command->error('Tidak ada user ditemukan.');
            return;
        }

        // -------------------------------------------------------
        // 2. Tentukan nomor surat (mulai dari 10)
        // -------------------------------------------------------
        $tahun      = date('Y');
        $bulanRoman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][date('n') - 1];
        $nomorUrut  = 10;
        $nomorSurat = sprintf('%03d.TGS/PTH1.30-GDE/%s/%s', $nomorUrut, $bulanRoman, $tahun);

        if (SuratSpd::where('nomor_surat', $nomorSurat)->exists()) {
            $this->command->warn("Surat {$nomorSurat} sudah ada, skip buat surat.");
            $surat = SuratSpd::where('nomor_surat', $nomorSurat)->first();
        } else {
            // -------------------------------------------------------
            // 3. Buat Surat SPD
            // -------------------------------------------------------
            DB::beginTransaction();
            try {
                $tanggalBerangkat = Carbon::today();
                $tanggalKembali   = Carbon::today()->addDays(2);

                $surat = new SuratSpd();
                $surat->nomor_urut            = $nomorUrut;
                $surat->nomor_surat           = $nomorSurat;
                $surat->pemberi_tugas_nama    = 'Wenny Kusumaningrum';
                $surat->pemberi_tugas_jabatan = 'Manager Unit Patuha';
                $surat->user_id               = $requester->id;
                $surat->pengemudi_nama        = $requester->nama_lengkap ?? $requester->name;
                $surat->tempat_tujuan         = 'Kantor Pusat Jakarta';
                $surat->melayani_bidang       = 'Operasi & Pemeliharaan';
                $surat->jumlah_pemakai        = 3;
                $surat->vehicle_id            = $vehicle->id;
                $surat->tanggal_berangkat     = $tanggalBerangkat;
                $surat->tanggal_kembali       = $tanggalKembali;
                $surat->lama_perjalanan_hari  = 3;
                $surat->total_budget          = 0;
                $surat->is_extension          = false;
                $surat->catatan               = 'Surat dummy untuk testing QR signature.';
                $surat->tanggal_surat         = 'Patuha, ' . Carbon::now()->translatedFormat('d F Y');
                $surat->status                = 'approved';
                $surat->approved_by           = $approver->id;
                $surat->approved_at           = now();
                $surat->lpj_deadline          = $tanggalKembali->copy()->addDays(3)->endOfDay();

                SuratSpd::withoutEvents(fn() => $surat->save());

                foreach ([
                    $requester->nama_lengkap ?? $requester->name,
                    'Budi Santoso',
                    'Dewi Rahayu',
                ] as $i => $nama) {
                    SuratSpdDetail::create([
                        'surat_spd_id' => $surat->id,
                        'nama_lengkap' => $nama,
                        'urutan'       => $i + 1,
                    ]);
                }

                $totalBudget = 0;
                for ($day = 0; $day <= $tanggalBerangkat->diffInDays($tanggalKembali); $day++) {
                    SuratSpdBudget::create([
                        'surat_spd_id' => $surat->id,
                        'tanggal'      => $tanggalBerangkat->copy()->addDays($day)->format('Y-m-d'),
                        'nominal'      => 500000,
                        'catatan'      => 'Budget harian testing',
                    ]);
                    $totalBudget += 500000;
                }

                SuratSpd::withoutEvents(fn() => $surat->update(['total_budget' => $totalBudget]));

                DB::commit();
                $this->command->info("✔ Surat SPD dibuat: {$surat->nomor_surat}");

            } catch (\Throwable $e) {
                DB::rollBack();
                $this->command->error('Gagal buat surat: ' . $e->getMessage());
                return;
            }
        }

        // -------------------------------------------------------
        // 4. Generate QR SVG + logo di tengah
        // -------------------------------------------------------
        $storageDir = storage_path('app/public/qr-surat');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $filename = 'QR_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $surat->nomor_surat) . '.svg';
        $fullPath = $storageDir . '/' . $filename;

        $qrData = json_encode([
            'id'                => $surat->id,
            'nomor_surat'       => $surat->nomor_surat,
            'authorizer'        => $surat->pemberi_tugas_nama,
            'tanggal_terbit'    => $surat->created_at->format('Y-m-d'),
            'tanggal_disetujui' => $surat->approved_at?->format('Y-m-d'),
        ]);

        try {
            $svgContent = $this->generateQrSvgWithLogo($qrData);
            file_put_contents($fullPath, $svgContent);
            $this->command->info("✔ QR signature dibuat: {$filename}");
        } catch (\Throwable $e) {
            $this->command->warn("⚠ QR gagal di-generate: " . $e->getMessage());
        }

        // create ke database
        SpdBarcodeSignature::updateOrCreate(
            ['spd_id' => $surat->id],
            ['signature_path' => 'qr-surat/' . $filename]
        );

        // -------------------------------------------------------
        // 5. Info URL print
        // -------------------------------------------------------
        $this->command->newLine();
        $this->command->info('Selesai! Buka URL ini untuk test print:');
        $this->command->line('  http://127.0.0.1:8000/spd/surat/' . urlencode($surat->nomor_surat) . '/print');
    }

    /**
     * Generate SVG QR code dengan logo tertanam di tengah.
     *
     * Cara kerja:
     * 1. BaconQrCode generate SVG (error correction HIGH supaya logo tidak ganggu scan)
     * 2. Logo PNG di-encode ke base64
     * 3. Inject <image> tag ke dalam SVG sebelum </svg>
     * 4. Tambah <rect> putih di belakang logo supaya bersih
     */
    private function generateQrSvgWithLogo(string $data): string
    {
        // -- Generate SVG dari BaconQrCode --
        $renderer = new ImageRenderer(
            new RendererStyle(self::QR_SIZE, 1),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $svg    = $writer->writeString($data);

        // -- Ambil viewBox dari SVG untuk hitung posisi logo --
        // BaconQrCode output SVG biasanya viewBox="0 0 {size} {size}"
        preg_match('/viewBox="0 0 (\d+(?:\.\d+)?) (\d+(?:\.\d+)?)"/', $svg, $matches);
        $svgW = isset($matches[1]) ? (float)$matches[1] : self::QR_SIZE;
        $svgH = isset($matches[2]) ? (float)$matches[2] : self::QR_SIZE;

        $logoW    = self::LOGO_SIZE;
        $logoH    = self::LOGO_SIZE;
        $logoX    = ($svgW - $logoW) / 2;
        $logoY    = ($svgH - $logoH) / 2;
        $padding  = 4;

        // -- Encode logo sebagai base64 --
        $logoPath   = public_path('DashboardTemplates/assets/img/geodipa-logo.png');
        $logoBase64 = '';
        $logoMime   = 'image/png';

        if (file_exists($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
        }

        // -- Bangun elemen SVG untuk logo --
        $logoElements = '';
        if ($logoBase64) {
            $rectX = $logoX - $padding;
            $rectY = $logoY - $padding;
            $rectW = $logoW + ($padding * 2);
            $rectH = $logoH + ($padding * 2);

            $logoElements = <<<SVG

    <!-- Logo di tengah QR -->
    <rect x="{$rectX}" y="{$rectY}" width="{$rectW}" height="{$rectH}" fill="white" rx="3" ry="3"/>
    <image href="data:{$logoMime};base64,{$logoBase64}"
           x="{$logoX}" y="{$logoY}"
           width="{$logoW}" height="{$logoH}"
           preserveAspectRatio="xMidYMid meet"/>
SVG;
        }

        // -- Inject sebelum </svg> --
        $svg = str_replace('</svg>', $logoElements . "\n</svg>", $svg);

        return $svg;
    }
}
