<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GasolineType;

class GasolineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

            GasolineType::create([
                'gasoline_code' => 'CN58',
                'gasoline_name' => 'Pertamina Dex',
                'price_per_liter' => 14000,
                'notes' => 'last updated 2025',
            ]);

            GasolineType::create([
                'gasoline_code' => 'RON95',
                'gasoline_name' => 'Pertamax',
                'price_per_liter' => 15000,
                'notes' => 'last updated 2025',
            ]);

    }
}
