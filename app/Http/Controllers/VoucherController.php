<?php

namespace App\Http\Controllers;
use App\Models\Permohonan;

use Illuminate\Http\Request;

class VoucherController extends Controller
{

    /**
     * Generate JSON response untuk Thermer App
     * Method ini akan dipanggil oleh Bluetooth Print App
     *
     * PENTING: endpoint ini diunduh langsung oleh aplikasi Android (bukan oleh
     * browser), sehingga TIDAK membawa cookie session. Karena itu route-nya
     * publik dan diamankan dengan signed URL (dibuat di
     * resources/views/requester/partials/voucher-print-modal.blade.php).
     * Bila signature tidak valid, tetap balas JSON — jangan HTML — supaya app
     * tidak menampilkan "Invalid json value".
     */
    public function thermerJsonResponse(Request $httpRequest, $requestId)
    {
        if (! $httpRequest->hasValidSignature()) {
            return response()->json([
                'error' => true,
                'message' => 'Link cetak tidak valid atau sudah kedaluwarsa. Muat ulang halaman lalu klik Print lagi.',
            ], 403);
        }

        try {
            $request = Permohonan::with(['requester', 'authorizer', 'vehicle', 'approvedRequest'])
                ->findOrFail($requestId);

            $printData = [];

            // 1. LOGO
            // Logo disimpan di public/img/ agar independen dari theme template.
            // Path di-resolve ke URL ABSOLUT yang reachable dari HP driver.
            // APP_PUBLIC_URL dipakai bila diisi; kalau kosong (default di .env)
            // fallback ke host request — host inilah yang dipakai app untuk
            // mengunduh JSON, jadi pasti bisa dijangkau dari perangkat yang sama.
            $publicBase = config('app.public_url') ?: url('/');
            $logoUrl = rtrim($publicBase, '/') . '/img/geodipa-logo.png';
            $printData[] = [
                'type' => 1, // image
                'path' => $logoUrl,
                'align' => 1 // center
            ];
            // 2. HEADER - Voucher BBM Operasional
            $printData[] = [
                'type' => 0, // text
                'content' => ' ', // empty line
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => 'Voucher BBM Operasional',
                'bold' => 1,
                'align' => 1, // center
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => 'Unit Patuha',
                'bold' => 1,
                'align' => 1,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => ' ',
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            // 3. NO REQUEST
            $printData[] = [
                'type' => 0,
                'content' => 'No. Request:',
                'bold' => 0,
                'align' => 1,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => $request->request_number,
                'bold' => 1,
                'align' => 1,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => str_repeat('-', 32),
                'bold' => 0,
                'align' => 1,
                'format' => 0
            ];

            // 4. PEMOHON
            $printData[] = [
                'type' => 0,
                'content' => 'Pemohon:',
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => $this->truncateText($request->requester->nama_lengkap ?? 'N/A', 32),
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => ' ',
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            // 5. ALAT/KENDARAAN
            $printData[] = [
                'type' => 0,
                'content' => 'Alat/Kendaraan:',
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => $request->vehicle->consumerial_code ?? 'N/A',
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => ' ',
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            // 6. KODE VOUCHER - BOLD & LARGE
            $printData[] = [
                'type' => 0,
                'content' => 'KODE VOUCHER',
                'bold' => 1,
                'align' => 1,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => $request->approvedRequest->voucher_code ?? 'N/A',
                'bold' => 1,
                'align' => 1,
                'format' => 2 // double height + width
            ];

            $printData[] = [
                'type' => 0,
                'content' => ' ',
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            // 7. NOMINAL
            $printData[] = [
                'type' => 0,
                'content' => 'Senilai',
                'bold' => 1,
                'align' => 1,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => 'Rp ' . number_format((float) $request->bill_amounts, 0, ',', '.'),
                'bold' => 1,
                'align' => 1,
                'format' => 2 // double height + width
            ];

            $printData[] = [
                'type' => 0,
                'content' => str_repeat('-', 32),
                'bold' => 0,
                'align' => 1,
                'format' => 0
            ];

            // 8. EXPIRED & APPROVAL
            $expiredDate = $request->expired_at
                ? $request->expired_at->translatedFormat('d M Y H:i')
                : ($request->authorization_date ? $request->authorization_date->addDays(7)->translatedFormat('d M Y H:i') : '-');

            $printData[] = [
                'type' => 0,
                'content' => 'Expired at:',
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => $expiredDate,
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => ' ',
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            // Relasi yang benar adalah authorizer(); sebelumnya memakai
            // $request->approver yang tidak ada sehingga selalu jatuh ke nama
            // pemohon dan bukan nama penyetuju.
            $approver = $request->authorizer->username
                ?? $request->authorizer->nama_lengkap
                ?? $request->requester->nama_lengkap
                ?? 'N/A';

            $printData[] = [
                'type' => 0,
                'content' => 'Approved by:',
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => $this->truncateText($approver, 32),
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => str_repeat('-', 32),
                'bold' => 0,
                'align' => 1,
                'format' => 0
            ];


            // 10. FOOTER
            $printData[] = [
                'type' => 0,
                'content' => 'General Affairs',
                'bold' => 0,
                'align' => 1,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => 'PT. Geo Dipa Energi (Persero)',
                'bold' => 0,
                'align' => 1,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => 'Unit Patuha',
                'bold' => 0,
                'align' => 1,
                'format' => 0
            ];

            // Empty lines for paper cut
            $printData[] = [
                'type' => 0,
                'content' => ' ',
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            $printData[] = [
                'type' => 0,
                'content' => ' ',
                'bold' => 0,
                'align' => 0,
                'format' => 0
            ];

            // Wajib JSON_FORCE_OBJECT: sample resmi aplikasi Thermer /
            // Bluetooth Print (assets/response.php di APK-nya) memakai
            // json_encode($a, JSON_FORCE_OBJECT), sehingga top-level-nya
            // objek {"0":{...},"1":{...}}, bukan array.
            return response()->json($printData, 200, [], JSON_FORCE_OBJECT);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Permohonan tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            // Tetap balas JSON supaya aplikasi tidak menampilkan
            // "Invalid json value" saat terjadi error di server.
            \Log::error('Thermer voucher JSON error: '.$e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Gagal menyiapkan data cetak.',
            ], 500);
        }
    }

    /**
     * Truncate text to max length
     */
    private function truncateText($text, $maxLength = 32)
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
}
