<?php

namespace App\Http\Controllers;
use App\Models\Permohonan;

use Illuminate\Http\Request;

class VoucherController extends Controller
{

    /**
     * Generate JSON response untuk Thermer App
     * Method ini akan dipanggil oleh Bluetooth Print App
     */
    public function thermerJsonResponse($requestId)
    {
        try {
            $request = Permohonan::with(['requester', 'vehicle', 'approvedRequest'])
                ->findOrFail($requestId);

            $printData = [];

            // 1. LOGO
            // Logo disimpan di public/img/ agar independen dari theme template.
            // Path di-resolve ke URL absolut yang reachable dari HP driver
            // (tidak pakai asset() karena bergantung APP_URL yang bisa localhost).
            $logoUrl = rtrim(config('app.public_url', config('app.url')), '/') . '/img/geodipa-logo.png';
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
                'content' => 'Rp ' . number_format($request->bill_amounts, 0, ',', '.'),
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

            $approver = $request->approver->username ?? $request->requester->nama_lengkap ?? 'N/A';

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

            // Return JSON response (force object seperti di dokumentasi)
            return response()->json($printData, 200, [], JSON_FORCE_OBJECT);

        } catch (\Exception $e) {
            // Return error dalam format JSON juga
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
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
