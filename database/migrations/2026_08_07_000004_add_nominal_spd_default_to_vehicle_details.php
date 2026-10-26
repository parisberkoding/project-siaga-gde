<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master Vehicle — tambah Default Nominal SPD.
     * nominal_spd_default = patokan budget default per SPD per hari,
     * dipakai saat form LPJ SPD mengisi nominal otomatis.
     */
    public function up(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->decimal('nominal_spd_default', 15, 2)->nullable()
                  ->after('kapasitas_bbm_tahunan')
                  ->comment('Nominal default budget SPD per hari untuk kendaraan ini');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->dropColumn('nominal_spd_default');
        });
    }
};
