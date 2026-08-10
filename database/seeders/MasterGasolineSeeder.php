<?php

namespace Database\Seeders;

use App\Models\MasterGasoline;
use App\Services\FuelBenchmarkService;
use App\Services\GasolinePriceSyncService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Throwable;

/**
 * Seed awal Master Gasoline (harga PERTAMAX & PERTAMINA DEX per region).
 * Menggunakan harga terverifikasi dari bensin-api (update Pertamina 2026-08-01),
 * lalu melakukan best-effort sinkronisasi langsung dari API.
 */
class MasterGasolineSeeder extends Seeder
{
    public function run(): void
    {
        $prices = [
            'PERTAMAX'       => 15950,
            'PERTAMINA DEX'  => 21150,
        ];

        $priceDate = Carbon::parse('2026-08-01T08:00:44.000Z');

        foreach (FuelBenchmarkService::REGIONS as $slug => $province) {
            foreach ($prices as $fuelType => $price) {
                MasterGasoline::updateOrCreate(
                    ['province_slug' => $slug, 'fuel_type' => $fuelType],
                    [
                        'province'        => $province,
                        'price_per_liter' => $price,
                        'availability'    => 'available',
                        'price_date'      => $priceDate,
                        'synced_at'       => now(),
                    ]
                );
            }
        }

        // Best-effort: ambil harga terbaru dari API bila jaringan tersedia.
        try {
            app(GasolinePriceSyncService::class)->sync();
        } catch (Throwable $e) {
            // Seed tetap pakai harga default di atas bila API tidak terjangkau.
        }
    }
}
