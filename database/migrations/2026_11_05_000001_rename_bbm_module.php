<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nama modul "BBM & Permohonan" menggantung (permohonan apa?). Modul ini khusus BBM kendaraan dinas
 * operasional, terpisah dari biaya perjalanan dinas di modul SPD. ModuleSeeder memakai nama yang sama;
 * migrasi ini untuk database yang seeder-nya tidak dijalankan ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')->where('code', 'bbm')->update([
            'name' => 'BBM Operasional',
            'description' => 'Siklus BBM operasional: permohonan voucher -> persetujuan -> laporan pembelian -> analisis efisiensi.',
        ]);
    }

    public function down(): void
    {
        DB::table('modules')->where('code', 'bbm')->update([
            'name' => 'BBM & Permohonan',
            'description' => 'Siklus BBM: permohonan -> approval -> bukti pembelian -> penarikan laporan.',
        ]);
    }
};
