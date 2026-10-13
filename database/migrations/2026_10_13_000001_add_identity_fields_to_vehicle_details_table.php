<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kelengkapan identitas kendaraan (detailing master vehicle).
     *
     * `vehicle_details` sudah menampung identitas teknis (jenis, merk, model,
     * tahun, nomor rangka, nomor mesin, warna). Migration ini melengkapinya
     * dengan identitas legal/administratif yang dibutuhkan untuk dokumentasi
     * aset & pengingat masa berlaku:
     *
     *   - nomor_bpkb / nomor_stnk / nomor_kir : nomor dokumen
     *   - pajak_berlaku_sampai / kir_berlaku_sampai : masa berlaku (date)
     *   - kapasitas_mesin (CC) & odometer (km) : data teknis operasional
     *   - status_kepemilikan : MILIK_SENDIRI | SEWA | PINJAM_PAKAI | LEASING
     *
     * Selain itu, `unit_id` pada vehicle_and_tools_consumers di-backfill dari
     * unit site kendaraan, supaya kendaraan lama ikut terlihat saat query
     * di-scope per unit (unit_id NULL sebelumnya tidak terlihat oleh GA unit).
     */
    public function up(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->string('nomor_bpkb', 100)->nullable()->after('nomor_mesin');
            $table->string('nomor_stnk', 100)->nullable()->after('nomor_bpkb');
            $table->string('nomor_kir', 100)->nullable()->after('nomor_stnk');
            $table->date('pajak_berlaku_sampai')->nullable()->after('nomor_kir');
            $table->date('kir_berlaku_sampai')->nullable()->after('pajak_berlaku_sampai');
            $table->unsignedSmallInteger('kapasitas_mesin')->nullable()->after('kir_berlaku_sampai')
                  ->comment('Kapasitas mesin dalam CC');
            $table->unsignedInteger('odometer')->nullable()->after('kapasitas_mesin')
                  ->comment('Pembacaan odometer terakhir (km)');
            $table->string('status_kepemilikan', 30)->nullable()->after('odometer')
                  ->comment('MILIK_SENDIRI | SEWA | PINJAM_PAKAI | LEASING');
        });

        $this->backfillUnitFromSite();
    }

    public function down(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_bpkb',
                'nomor_stnk',
                'nomor_kir',
                'pajak_berlaku_sampai',
                'kir_berlaku_sampai',
                'kapasitas_mesin',
                'odometer',
                'status_kepemilikan',
            ]);
        });
    }

    /**
     * Isi unit_id kendaraan dari unit site-nya (hanya yang masih NULL).
     *
     * Ditulis portabel (tanpa UPDATE ... JOIN) agar tetap jalan di MySQL
     * produksi maupun SQLite saat test suite memakai RefreshDatabase.
     */
    private function backfillUnitFromSite(): void
    {
        if (! Schema::hasTable('vehicle_details') || ! Schema::hasTable('sites')) {
            return;
        }

        $pairs = DB::table('vehicle_details')
            ->join('sites', 'sites.id', '=', 'vehicle_details.site_id')
            ->whereNotNull('sites.unit_id')
            ->select('vehicle_details.vehicle_and_tools_consumer_id', 'sites.unit_id')
            ->get();

        foreach ($pairs as $pair) {
            DB::table('vehicle_and_tools_consumers')
                ->where('id', $pair->vehicle_and_tools_consumer_id)
                ->whereNull('unit_id')
                ->update(['unit_id' => $pair->unit_id]);
        }
    }
};
