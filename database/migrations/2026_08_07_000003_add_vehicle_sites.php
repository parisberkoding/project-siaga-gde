<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seeder unit kerja kendaraan — custom/additive, di luar PMU & Patuha.
     * sites sudah menjadi master table, penambahan unit cukup lewat data.
     * Contoh unit baru: Dieng (operasional lapangan) & Pusat (kantor pusat).
     */
    public function up(): void
    {
        $units = [
            ['code' => 'Dieng', 'name' => 'Dieng'],
            ['code' => 'Pusat', 'name' => 'Pusat'],
        ];

        foreach ($units as $unit) {
            $exists = DB::table('sites')->where('code', $unit['code'])->exists();

            if (! $exists) {
                DB::table('sites')->insert([
                    'code'       => $unit['code'],
                    'name'       => $unit['name'],
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (['Dieng', 'Pusat'] as $code) {
            $siteId = DB::table('sites')->where('code', $code)->value('id');
            if (! $siteId) {
                continue;
            }

            $referenced = Schema::hasTable('vehicle_details')
                && DB::table('vehicle_details')->where('site_id', $siteId)->exists();

            if ($referenced) {
                DB::table('sites')->where('id', $siteId)->update(['is_active' => false]);
            } else {
                DB::table('sites')->where('id', $siteId)->delete();
            }
        }
    }
};
