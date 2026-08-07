<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master Vehicle (spiderweb) — tambah kolom budget & unit asal.
     * vehicle_details sudah menampung data teknis (merk, model, tangki,
     * kapasitas). Di sini hanya di-EXTEND, bukan membuat tabel baru.
     */
    public function up(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->nullable()->after('vehicle_and_tools_consumer_id');
            $table->foreign('site_id', 'fk_vd_site')
                  ->references('id')->on('sites')
                  ->onDelete('set null');

            $table->decimal('nominal_full_tank', 15, 2)->nullable()
                  ->after('volume_tangki')
                  ->comment('Nominal default untuk 1x pengisian full tank (budget operasional)');

            $table->decimal('kapasitas_bbm_tahunan', 15, 2)->nullable()
                  ->after('nominal_full_tank')
                  ->comment('Kuota BBM tahunan dalam liter');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->dropForeign('fk_vd_site');
            $table->dropColumn(['site_id', 'nominal_full_tank', 'kapasitas_bbm_tahunan']);
        });
    }
};
