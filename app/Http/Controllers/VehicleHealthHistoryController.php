<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleBarcode;
use App\Models\VehicleCheckingDetail;
use ZipArchive;

class VehicleHealthHistoryController extends Controller
{
    /**
     * Tampilan kamera scan QR code kendaraan.
     * Accessible oleh: dispatcher, superadmin, admin
     */
    public function scanView()
    {
        return view('dispatcher.barcode-scan.index');
    }

    /**
     * Tampilkan riwayat pengecekan kendaraan berdasarkan hasil scan QR code.
     * QR code mengandung JSON: {"id": N, "value": "PLATE|NAME"}
     * Manual input: consumerial_code (plat nomor)
     *
     * GET /dispatcher/scan-vehicle/result?code={payload}
     */
    public function vehicleHistory(Request $request)
    {
        $raw = $request->query('code');

        if (empty($raw)) {
            return redirect()->route('dispatcher.scan-vehicle')
                ->with('error', 'Kode kendaraan tidak ditemukan. Silakan scan ulang.');
        }

        // Coba parse sebagai JSON (hasil scan QR code)
        $decoded = json_decode($raw, true);

        if (is_array($decoded) && isset($decoded['id'])) {
            $vehicle = VehicleAndToolsConsumers::find($decoded['id']);
        } else {
            // Fallback: input manual berdasarkan consumerial_code
            $vehicle = VehicleAndToolsConsumers::where('consumerial_code', $raw)->first();
        }

        if (!$vehicle) {
            return redirect()->route('dispatcher.scan-vehicle')
                ->with('error', "Kendaraan tidak ditemukan dalam sistem. Pastikan QR code atau kode kendaraan benar.");
        }

        // Load semua checking detail milik kendaraan ini
        $checkings = VehicleCheckingDetail::whereHas('vehicleChecking', function ($q) use ($vehicle) {
                $q->where('vehicle_id', $vehicle->id);
            })
            ->with([
                'vehicleChecking',
                'itemResults',
                'dispatcher:id,nama_lengkap',
                'currentDriver:id,nama_lengkap',
            ])
            ->orderBy('checked_at', 'desc')
            ->get();

        $checkingsByType = [
            'body'    => $checkings->filter(fn($d) => $d->type === 'body'),
            'daily'   => $checkings->filter(fn($d) => $d->type === 'routine' && $d->sub_type === 'daily'),
            'weekly'  => $checkings->filter(fn($d) => $d->type === 'routine' && $d->sub_type === 'weekly'),
            'monthly' => $checkings->filter(fn($d) => $d->type === 'routine' && $d->sub_type === 'monthly'),
        ];

        $barcode = $vehicle->barcode;

        return view('dispatcher.barcode-scan.result', compact('vehicle', 'checkingsByType', 'barcode'));
    }

    /**
     * Tampilan kamera scan QR code untuk Dispatcher Scanner.
     * Setelah scan, dispatcher bisa memilih jenis pemeriksaan.
     */
    public function dispatcherScanView()
    {
        return view('dispatcher.dispatcher-scanner.index');
    }

    /**
     * Tampilkan hasil scan untuk Dispatcher Scanner.
     * Menampilkan info kendaraan + tombol pilih jenis pemeriksaan.
     */
    public function dispatcherScanResult(Request $request)
    {
        $raw = $request->query('code');

        if (empty($raw)) {
            return redirect()->route('dispatcher.dispatcher-scanner')
                ->with('error', 'Kode kendaraan tidak ditemukan. Silakan scan ulang.');
        }

        $decoded = json_decode($raw, true);

        if (is_array($decoded) && isset($decoded['id'])) {
            $vehicle = VehicleAndToolsConsumers::find($decoded['id']);
        } else {
            $vehicle = VehicleAndToolsConsumers::where('consumerial_code', $raw)->first();
        }

        if (!$vehicle) {
            return redirect()->route('dispatcher.dispatcher-scanner')
                ->with('error', "Kendaraan tidak ditemukan dalam sistem. Pastikan QR code atau kode kendaraan benar.");
        }

        $barcode = $vehicle->barcode;

        return view('dispatcher.dispatcher-scanner.result', compact('vehicle', 'barcode'));
    }

    /**
     * Download QR code satu kendaraan.
     * Accessible oleh: superadmin, dispatcher
     */
    public function downloadBarcode($vehicleId)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'dispatcher', 'admin'])) {
            abort(403, 'Anda tidak memiliki akses untuk mengunduh QR code.');
        }

        $barcode = VehicleBarcode::where('vehicle_and_tools_consumer_id', $vehicleId)->first();

        if (!$barcode || !file_exists($barcode->full_path)) {
            abort(404, 'File QR code tidak ditemukan. Silakan jalankan VehicleBarcodeSeeder terlebih dahulu.');
        }

        return response()->download($barcode->full_path, basename($barcode->barcode_path));
    }

    /**
     * Download semua QR code kendaraan sebagai satu file ZIP.
     * Accessible oleh: superadmin
     */
    public function downloadAllBarcodesZip()
    {
        $barcodes = VehicleBarcode::all();

        if ($barcodes->isEmpty()) {
            return back()->with('error', 'Belum ada QR code kendaraan. Silakan jalankan VehicleBarcodeSeeder terlebih dahulu.');
        }

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipName = 'qrcode-kendaraan-' . now()->format('Ymd-His') . '.zip';
        $zipPath = $tmpDir . '/' . $zipName;

        $zip    = new ZipArchive();
        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            abort(500, 'Gagal membuat file ZIP.');
        }

        $added = 0;
        foreach ($barcodes as $barcode) {
            if (file_exists($barcode->full_path)) {
                $zip->addFile($barcode->full_path, basename($barcode->barcode_path));
                $added++;
            }
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipPath);
            return back()->with('error', 'Tidak ada file QR code yang tersedia di storage.');
        }

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }
}
