<?php

namespace Database\Factories;

use App\Models\VehicleAndToolsConsumers;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleDetailFactory extends Factory
{
    protected $model = \App\Models\VehicleDetail::class;

    public function definition(): array
    {
        return [
            'vehicle_and_tools_consumer_id' => VehicleAndToolsConsumers::factory(),
            'site_id' => null,
            'jenis_kendaraan' => 'SUV',
            'merk' => $this->faker->company,
            'model' => $this->faker->word,
            'tahun_pembuatan' => $this->faker->year,
            'nomor_rangka' => null,
            'nomor_mesin' => null,
            'warna' => $this->faker->safeColorName,
            'kapasitas_penumpang' => 7,
            'kapasitas_bahan_bakar' => 45,
            'jenis_bahan_bakar' => 'Pertamax',
            'volume_tangki' => 45,
            'nominal_full_tank' => 500000,
            'kapasitas_bbm_tahunan' => 15000000,
            'foto' => null,
            'catatan' => null,
        ];
    }
}
