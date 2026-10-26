<?php

namespace App\Http\Controllers;

use App\Models\LpjPenggantian;
use App\Models\SpdBarcodeSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * SpdPrintController — CETAK DOKUMEN GABUNGAN SPD.
 *
 * Menghasilkan satu dokumen A4 berisi:
 *  1. Surat Tugas (SPD)
 *  2. Lembar Ringkasan Penggunaan Biaya Perjalanan Dinas
 *  3. [KHUSUS GA] Lembar Lampiran Pemberian Dana Perjalanan Dinas
 *  4. Lembar Persetujuan Penggantian Pertanggungjawaban Pembayaran
 *  5. Lampiran Penggunaan Dana (foto-foto struk)
 *
 * Lembar Pemberian Dana memuat data pencairan (Tunai/Transfer + nomor
 * referensi perbankan + rekening tujuan) sehingga HANYA disertakan bila
 * pemilik sesi punya permission `lpj.pencairan` (Divisi GA / superadmin).
 * Lembar ini juga bisa diunduh terpisah lewat exportPemberianDana().
 */
class SpdPrintController extends Controller
{
    /**
     * Export dokumen gabungan Surat SPD + LPJ.
     *
     * @param \App\Models\LpjPenggantian $laporan
     */
    public function exportLpj(Request $request, LpjPenggantian $laporan)
    {
        $laporan = LpjPenggantian::with([
            'suratSpd.details',
            'suratSpd.vehicle',
            'suratSpd.vehicle.detail',
            'suratSpd.parentSurat',
            'suratSpd.approver',
            'suratSpd.user',
            'suratSpd.pencairans',
            'vehicle',
            'vehicle.detail',
            'user',
            'approverPengguna',
            'approverDivisiGa',
            'approverManagement',
            'strukItems',
            'details',
        ])->findOrFail($laporan->id);

        $surat = $laporan->suratSpd;

        if (! $surat) {
            abort(404, 'Surat SPD tidak ditemukan untuk LPJ ini.');
        }

        $spending = $laporan->getSpendingPerKategori();
        $rincian = $laporan->getRincianPerTanggal();
        $grandTotalRincian = $laporan->getGrandTotalRincian();
        $fotoItems = $laporan->getStrukItemsOrderedForPhotos();

        // Lembar Pemberian Dana hanya untuk sisi General Affairs.
        $includePemberianDana = $request->user()?->hasPermission('lpj.pencairan') ?? false;
        $pencairans = $surat->pencairans;

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

        // Barcode/QR persetujuan pada Lembar Persetujuan Penggantian Pertanggungjawaban
        $qrPengguna = $this->qrForApprover($laporan, 'pengguna');
        $qrDivisiGa = $this->qrForApprover($laporan, 'divisi_ga');

        // Kop surat di-embed base64
        $logoBase64 = $this->logoBase64();

        return view('templates.template-surat.template-spd-lpj', compact(
            'laporan',
            'surat',
            'spending',
            'rincian',
            'grandTotalRincian',
            'fotoPages',
            'photoGrid',
            'qrSvgContent',
            'qrPengguna',
            'qrDivisiGa',
            'logoBase64',
            'includePemberianDana',
            'pencairans'
        ));
    }

    /**
     * Unduh terpisah: Lembar Lampiran Pemberian Dana Perjalanan Dinas.
     * Hanya untuk Divisi GA / superadmin (permission `lpj.pencairan`).
     */
    public function exportPemberianDana(LpjPenggantian $laporan)
    {
        $laporan = LpjPenggantian::with([
            'suratSpd.details',
            'suratSpd.vehicle',
            'suratSpd.pencairans',
            'suratSpd.user',
            'vehicle',
            'user',
        ])->findOrFail($laporan->id);

        $surat = $laporan->suratSpd;

        if (! $surat) {
            abort(404, 'Surat SPD tidak ditemukan untuk LPJ ini.');
        }

        return view('templates.template-surat.template-pemberian-dana', [
            'laporan'     => $laporan,
            'surat'       => $surat,
            'pencairans'  => $surat->pencairans,
            'logoBase64'  => $this->logoBase64(),
        ]);
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

    /**
     * QR tanda tangan persetujuan (barcode) untuk Lembar Persetujuan
     * Penggantian Pertanggungjawaban.
     *
     * @param  string  $peran  'pengguna' | 'divisi_ga'
     */
    private function qrForApprover(LpjPenggantian $laporan, string $peran): ?string
    {
        $approver = $peran === 'divisi_ga'
            ? $laporan->approverDivisiGa
            : $laporan->approverPengguna;

        $waktu = $peran === 'divisi_ga'
            ? $laporan->approved_divisi_ga_at
            : $laporan->approved_pengguna_at;

        if (! $approver || ! $waktu) {
            return null; // belum divalidasi → kolom barcode dibiarkan kosong
        }

        try {
            $qrData = json_encode([
                'dokumen'      => 'Lembar Persetujuan Penggantian Pertanggungjawaban',
                'lpj_number'   => $laporan->lpj_number,
                'nomor_surat'  => $laporan->suratSpd?->nomor_surat,
                'peran'        => $peran === 'divisi_ga' ? 'Divisi GA' : 'Pengguna',
                'nama'         => $approver->nama_lengkap ?? $approver->name,
                'jabatan'      => $approver->jabatan,
                'divalidasi'   => $waktu->format('Y-m-d H:i'),
                'total_biaya'  => (float) $laporan->total_pengeluaran,
            ]);

            $renderer = new ImageRenderer(
                new RendererStyle(300, 1),
                new SvgImageBackEnd()
            );

            return (new Writer($renderer))->writeString($qrData);
        } catch (\Throwable $e) {
            \Log::warning('QR approver (SpdPrintController) gagal: ' . $e->getMessage(), [
                'lpj_id' => $laporan->id,
                'peran'  => $peran,
            ]);

            return null;
        }
    }
}
