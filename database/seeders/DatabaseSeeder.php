<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            // UserSeeder::class,
            // VTCSeeder::class,
            // GasolineSeeder::class,
            // PermohonanSeeder::class,
            ImportPreprocessedDataSeeder::class,
            VehiclePartSeeder::class,
            VehicleBarcodeSeeder::class,
            SuratSpdWithQrSeeder::class,
            RbacSeeder::class,
            UnitSeeder::class,
            // HARUS setelah RbacSeeder (butuh tabel permissions) dan UnitSeeder
            // (butuh baris units untuk mengisi matrix unit x modul).
            ModuleSeeder::class,
            LunchDemoSeeder::class,
            SpdDemoSeeder::class,
            VehicleDemoSeeder::class,
            MasterGasolineSeeder::class,
            VehicleCapacitySeeder::class,
        ]);
    }
}
