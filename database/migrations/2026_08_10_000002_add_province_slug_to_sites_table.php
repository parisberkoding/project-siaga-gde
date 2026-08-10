<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mapping site/unit ke region provinsi untuk acuan harga BBM benchmark.
     * - PMU, PTH, PTH-BC -> Jawa Barat
     * - Dieng            -> Jawa Tengah
     * - Pusat            -> DKI Jakarta
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('province_slug', 100)->nullable()->after('name');
        });

        $map = [
            'PMU'    => 'jawa-barat',
            'PTH'    => 'jawa-barat',
            'PTH-BC' => 'jawa-barat',
            'Dieng'  => 'jawa-tengah',
            'Pusat'  => 'dki-jakarta',
        ];

        foreach ($map as $code => $slug) {
            DB::table('sites')->where('code', $code)->update(['province_slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('province_slug');
        });
    }
};
