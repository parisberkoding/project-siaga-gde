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
            // HARUS setelah RbacSeeder (butuh tabel permissions dan role
            // superadmin) dan UnitSeeder (butuh unit PUSAT + site-nya).
            SuperadminPusatSeeder::class,
            // Butuh role developers (dari RbacSeeder) dan unit PUSAT.
            DeveloperUserSeeder::class,
            // HARUS setelah RbacSeeder (butuh tabel permissions) dan UnitSeeder
            // (butuh baris units untuk mengisi matrix unit x modul).
            ModuleSeeder::class,
            // Butuh tabel modules (untuk module_id) — jadi setelah ModuleSeeder.
            FeatureSeeder::class,
            LunchDemoSeeder::class,
            SpdDemoSeeder::class,
            VehicleDemoSeeder::class,
            MasterGasolineSeeder::class,
            VehicleCapacitySeeder::class,
        ]);
    }
}
