<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Memutus dependency terakhir Lunch → Vehicle Tracking dan menjadikan
     * tabel ini murni tabel FORECAST (lunch_forecasts):
     *
     *  - source_trip_id di-drop: forecast tidak boleh lagi menunjuk trip.
     *    Actual sekarang datang dari lunch_transactions (QR), bukan trip.
     *  - prediction_source enum → string: enum lama mengunci nilai
     *    'vehicle' di level schema. String membebaskan penambahan sumber
     *    baru (confirmation, dsb.) tanpa ALTER, dan nilai historis
     *    'vehicle' di-relabel 'legacy_vehicle' agar jelas itu data era
     *    lama yang tidak lagi diproduksi.
     *  - employee_default_id ditambahkan: forecast per karyawan master
     *    (bukan per akun login), konsisten dengan Identity Resolution.
     *    user_id lama dibiarkan untuk kesinambungan data historis.
     */
    public function up(): void
    {
        Schema::table('lunch_trackings', function (Blueprint $table) {
            $table->foreignId('employee_default_id')->nullable()->after('user_id')
                ->constrained('employee_defaults')->nullOnDelete();

            // user_id dulunya wajib (setiap baris berasal dari trip milik
            // akun). Forecast V2 berbasis employee master — karyawan tanpa
            // akun login sah, jadi user_id kini opsional.
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        // Petakan baris historis ke master karyawan via user_id.
        DB::statement('
            UPDATE lunch_trackings
            SET employee_default_id = (
                SELECT id FROM employee_defaults
                WHERE employee_defaults.user_id = lunch_trackings.user_id
            )
        ');

        Schema::table('lunch_trackings', function (Blueprint $table) {
            $table->string('prediction_source', 30)->default('default')->change();
        });

        DB::table('lunch_trackings')
            ->where('prediction_source', 'vehicle')
            ->update(['prediction_source' => 'legacy_vehicle']);

        Schema::table('lunch_trackings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_trip_id');
        });

        Schema::rename('lunch_trackings', 'lunch_forecasts');
    }

    public function down(): void
    {
        Schema::rename('lunch_forecasts', 'lunch_trackings');

        Schema::table('lunch_trackings', function (Blueprint $table) {
            $table->foreignId('source_trip_id')->nullable()
                ->constrained('trips')->nullOnDelete();
        });

        DB::table('lunch_trackings')
            ->where('prediction_source', 'legacy_vehicle')
            ->update(['prediction_source' => 'vehicle']);

        Schema::table('lunch_trackings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_default_id');
        });
    }
};
