<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cluster A — isolasi data antar unit.
     *
     * Tambah kolom unit_id pada permohonans, approved_requests, surat_spds,
     * dan master BBM operasional (vehicle_and_tools_consumers).
     *
     * Kolom di 3 tabel utama TIDAK ada di DB meski sudah dideklarasikan di
     * migration create-nya, karena migration create tersebut sudah terlanjur
     * dijalankan sebelum kolom ditambahkan. Maka ditambahkan di sini.
     *
     * Backfill data historis dilakukan terpisah di UnitSeeder (bukan di sini),
     * karena sumber acuannya (sites.unit_id / users.unit_id) baru terisi setelah
     * seeder menjalankan pemetaan site→unit.
     */
    public function up(): void
    {
        foreach (['permohonans', 'approved_requests', 'surat_spds', 'vehicle_and_tools_consumers'] as $table) {
            if (Schema::hasColumn($table, 'unit_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['permohonans', 'approved_requests', 'surat_spds', 'vehicle_and_tools_consumers'] as $table) {
            if (Schema::hasColumn($table, 'unit_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['unit_id']);
                    $table->dropColumn('unit_id');
                });
            }
        }
    }
};
