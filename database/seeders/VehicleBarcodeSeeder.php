<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleBarcode;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class VehicleBarcodeSeeder extends Seeder
{
    private const QR_SIZE = 300;

    public function run(): void
    {
        $storageDir = storage_path('app/public/barcode-vehicle');

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $vehicles = VehicleAndToolsConsumers::all();

        if ($vehicles->isEmpty()) {
            $this->command->warn('Tidak ada data kendaraan ditemukan.');
            return;
        }

        $renderer = new ImageRenderer(
            new RendererStyle(self::QR_SIZE, 1),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);

        $success = 0;
        $failed  = 0;

        foreach ($vehicles as $vehicle) {
            // Simpan sebagai .svg supaya file bisa dibuka
            $safeFilename = preg_replace('/[^A-Za-z0-9\-]/', '_', $vehicle->consumerial_code) . '.svg';
            $destPath     = $storageDir . '/' . $safeFilename;
            $relativePath = 'barcode-vehicle/' . $safeFilename;

            $qrData = json_encode([
                'id'    => $vehicle->id,
                'value' => $vehicle->consumerial_code . '|' . ($vehicle->consumerial_name ?? ''),
            ]);

            try {
                $writer->writeFile($qrData, $destPath);

                VehicleBarcode::updateOrCreate(
                    ['vehicle_and_tools_consumer_id' => $vehicle->id],
                    ['barcode_path' => $relativePath]
                );

                $this->command->info("✔ QR dibuat: {$safeFilename} ({$vehicle->consumerial_code})");
                $success++;
            } catch (\Throwable $e) {
                $this->command->warn("✘ Gagal '{$vehicle->consumerial_code}': " . $e->getMessage());
                $failed++;
            }
        }

        $this->command->newLine();
        $this->command->info("VehicleBarcodeSeeder selesai. Berhasil: {$success}, Gagal: {$failed}.");
    }
}
