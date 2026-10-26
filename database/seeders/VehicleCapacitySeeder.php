<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleDetail;
use App\Services\FuelBenchmarkService;
use Illuminate\Database\Seeder;

/**
 * Seeder kapasitas tangki & penumpang per kendaraan (Master Vehicle).
 * Isi vehicle_details yang masih kosong supaya benchmark BBM bisa dihitung
 * per unit: volume_tangki x harga BBM (region unit) -> kelipatan 50.000.
 */
class VehicleCapacitySeeder extends Seeder
{
    /**
     * Spesifikasi default per tipe armada.
     * [keyword nama, kapasitas_penumpang, volume_tangki (liter), jenis_bahan_bakar]
     */
    protected const SPECS = [
        ['CRV HYBRID',            5,  57, 'Pertamax'],
        ['INNOVA ZENIX HYBRID',   7,  55, 'Pertamax'],
        ['INNOVA REBORN',         7,  55, 'Pertamax'],
        ['SINGLE CABIN',          2,  60, 'Pertamina Dex'],
        ['DOUBLE CABIN',          5,  60, 'Pertamina Dex'],
        ['CABIN',                 2,  60, 'Pertamina Dex'],
        ['CANTER',               15,  70, 'Pertamina Dex'],
        ['HIACE',                15,  70, 'Pertamina Dex'],
        ['AMBULANCE',             4,  70, 'Pertamina Dex'],
        ['MITSUBISHI',            6,  60, 'Pertamina Dex'],
    ];

    public function run(): void
    {
        $defaultSite = Site::whereIn('code', ['PTH', 'Patuha'])->first();

        VehicleAndToolsConsumers::where('consumerial_type', 'KENDARAAN')->get()
            ->each(function (VehicleAndToolsConsumers $vehicle) use ($defaultSite) {
                [$passengers, $tank, $fuel] = $this->specFor($vehicle->consumerial_name);

                $siteId = $defaultSite?->id;

                $detail = VehicleDetail::firstOrNew(
                    ['vehicle_and_tools_consumer_id' => $vehicle->id]
                );

                $detail->site_id = $siteId;
                $detail->kapasitas_penumpang = $passengers;
                $detail->volume_tangki = $tank;
                $detail->kapasitas_bahan_bakar = $tank;
                $detail->jenis_bahan_bakar = $fuel;
                $detail->save();

                // nominal_full_tank = benchmark sesuai harga region produk BBM.
                $detail->nominal_full_tank = FuelBenchmarkService::benchmarkForVehicle($vehicle);
                $detail->save();
            });
    }

    /**
     * Pilih spesifikasi yang cocok dengan nama kendaraan (berurutan, lebih spesifik dulu).
     */
    protected function specFor(string $name): array
    {
        $upper = strtoupper($name);

        foreach (self::SPECS as $spec) {
            if (str_contains($upper, strtoupper($spec[0]))) {
                return [$spec[1], $spec[2], $spec[3]];
            }
        }

        return [7, 55, 'Pertamax'];
    }
}
