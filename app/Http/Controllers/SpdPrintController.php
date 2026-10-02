<?php

namespace App\Http\Controllers;

use App\Models\LpjPenggantian;
use App\Models\SpdBarcodeSignature;
use Illuminate\Support\Facades\Storage;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * SpdPrintController — CETAK DOKUMEN GABUNGAN SPD.
 *
 * Menghasilkan satu dokumen A4 (margin 3 cm semua sisi) berisi:
 *  1. Page 1 : Ringkasan Surat Tugas (Kop Surat + data surat + daftar nama)
 *  2. Page 2 : Ringkasan Laporan LPJ (spending + odometer)
 *  3. Page 3 : Rincian Penggunaan (tabel pivot tanggal + TTD)
 *  4. Page 4+ : Lampiran foto-foto struk (masonry grid, 6 foto/halaman)
 *
 * Dokumen ini bisa dicetak oleh siapa pun yang punya hak akses melihat LPJ
 * (driver / requester / admin / superadmin / manager / dispatcher).
 */
class SpdPrintController extends Controller
{
    /**
     * Export dokumen gabungan Surat SPD + LPJ.
     *
     * @param \App\Models\LpjPenggantian $laporan
     */
    public function exportLpj(LpjPenggantian $laporan)
    {
        $laporan = LpjPenggantian::with([
            'suratSpd.details',
            'suratSpd.vehicle',
            'suratSpd.vehicle.detail',
            'suratSpd.parentSurat',
            'suratSpd.approver',
            'suratSpd.user',
            'vehicle',
            'vehicle.detail',
            'user',
            'approverPengguna',
            'approverDivisiGa',
            'approverManagement',
            'strukItems',
        ])->findOrFail($laporan->id);

        $surat = $laporan->suratSpd;

        if (! $surat) {
            abort(404, 'Surat SPD tidak ditemukan untuk LPJ ini.');
        }

        $spending = $laporan->getSpendingPerKategori();
        $rincian = $laporan->getRincianPerTanggal();
        $fotoItems = $laporan->getStrukItemsOrderedForPhotos();

        // Foto per halaman: 1 halaman = 2 kolom x 3 baris = 6 foto
        $fotoPages = $fotoItems->count() > 0 ? $fotoItems->chunk(6) : collect();

        // Embed foto ke base64 supaya tampil saat cetak (tanpa server running).
        $photoGrid = $fotoPages->map(function ($page) {
            return $page->map(function ($item) {
                return [
                    'item' => $item,
                    'image_base64' => $this->toBase64($item->path_image),
                    'mime' => $this->detectMime($item->path_image),
                ];
            });
        });

        // QR signature dari surat (di-generate/cache seperti template-spd)
        $qrSvgContent = $this->qrForSurat($surat);

        // Kop surat di-embed base64
        $logoBase64 = $this->logoBase64();

        return view('templates.template-surat.template-spd-lpj', compact(
            'laporan',
            'surat',
            'spending',
            'rincian',
            'fotoPages',
            'photoGrid',
            'qrSvgContent',
            'logoBase64'
        ));
    }

    /**
     * Baca gambar struk dari storage public lalu encode jadi base64 data URI.
     * Return null bila file tidak ditemukan.
     */
    private function toBase64(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            if (! Storage::disk('public')->exists($path)) {
                return null;
            }

            return base64_encode(Storage::disk('public')->get($path));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Deteksi mime dari path storage public.
     */
    private function detectMime(?string $path): string
    {
        try {
            if ($path && Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->mimeType($path) ?? 'image/jpeg';
            }
        } catch (\Throwable $e) {
            // fallthrough
        }

        return 'image/jpeg';
    }

    /**
     * Logo kop surat sebagai data URI (fallback safe jika file tidak ada).
     */
    private function logoBase64(): ?string
    {
        $logoPath = public_path('DashboardTemplates/assets/img/geodipa-logo.png');

        if (file_exists($logoPath)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        return null;
    }

    /**
     * QR signature surat — di-generate sekali lalu di-cache di DB (SpdBarcodeSignature).
     */
    private function qrForSurat($surat): ?string
    {
        try {
            $record = SpdBarcodeSignature::where('spd_id', $surat->id)->first();

            if ($record && $record->signature_path && file_exists(storage_path('app/public/' . $record->signature_path))) {
                return file_get_contents(storage_path('app/public/' . $record->signature_path));
            }

            $storageDir = storage_path('app/public/qr-surat');
            if (! is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $filename = 'QR_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $surat->nomor_surat) . '.svg';
            $relativePath = 'qr-surat/' . $filename;
            $fullPath = $storageDir . '/' . $filename;

            $qrData = json_encode([
                'id'                => $surat->id,
                'nomor_surat'       => $surat->nomor_surat,
                'authorizer'        => $surat->pemberi_tugas_nama,
                'tanggal_terbit'    => $surat->created_at->format('Y-m-d'),
                'tanggal_disetujui' => $surat->approved_at?->format('Y-m-d'),
            ]);

            $renderer = new ImageRenderer(
                new RendererStyle(300, 1),
                new SvgImageBackEnd()
            );
            $svgContent = (new Writer($renderer))->writeString($qrData);

            file_put_contents($fullPath, $svgContent);

            SpdBarcodeSignature::updateOrCreate(
                ['spd_id' => $surat->id],
                ['signature_path' => $relativePath]
            );

            return $svgContent;
        } catch (\Throwable $e) {
            \Log::warning('QR signature (SpdPrintController) gagal: ' . $e->getMessage(), [
                'surat_id' => $surat->id,
            ]);

            return null;
        }
    }
}
