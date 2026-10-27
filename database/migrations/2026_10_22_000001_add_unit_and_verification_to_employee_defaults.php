<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Employee Default kini menjadi bagian dari hierarki Unit → Site.
 *
 * Sebelumnya unit karyawan hanya bisa diturunkan dari default_site_id. Itu
 * tidak cukup untuk dua kasus:
 *   1. pegawai non-SSO yang mendaftar lewat scan QR dan belum punya site
 *      default (unit tetap harus diketahui agar pilihan "besok makan dimana"
 *      bisa dibatasi);
 *   2. karyawan yang dipindahkan site-nya tetapi tetap di unit yang sama.
 *
 * `verification_status` menandai karyawan yang dibuat otomatis dari scan
 * publik. Mereka tetap bisa check-in, tetapi admin unit perlu memverifikasi
 * sebelum dipakai untuk forecast jangka panjang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_defaults', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_defaults', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('default_site_id')
                    ->constrained('units')->nullOnDelete();
            }

            if (! Schema::hasColumn('employee_defaults', 'verification_status')) {
                $table->string('verification_status', 20)->default('verified')->after('is_active');
            }

            if (! Schema::hasColumn('employee_defaults', 'source')) {
                $table->string('source', 20)->default('admin')->after('verification_status');
            }

            if (! Schema::hasColumn('employee_defaults', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('source')
                    ->constrained('users')->nullOnDelete();
            }
        });

        // Backfill unit dari site default. Karyawan tanpa site dibiarkan NULL;
        // UnitScope akan memperlakukannya sebagai "belum di-assign".
        DB::table('employee_defaults')
            ->whereNull('unit_id')
            ->whereNotNull('default_site_id')
            ->orderBy('id')
            ->chunk(200, function ($rows) {
                $sites = DB::table('sites')
                    ->whereIn('id', $rows->pluck('default_site_id')->unique()->all())
                    ->pluck('unit_id', 'id');

                foreach ($rows as $row) {
                    $unitId = $sites[$row->default_site_id] ?? null;
                    if ($unitId) {
                        DB::table('employee_defaults')->where('id', $row->id)->update(['unit_id' => $unitId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('employee_defaults', function (Blueprint $table) {
            if (Schema::hasColumn('employee_defaults', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            if (Schema::hasColumn('employee_defaults', 'source')) {
                $table->dropColumn('source');
            }
            if (Schema::hasColumn('employee_defaults', 'verification_status')) {
                $table->dropColumn('verification_status');
            }
            if (Schema::hasColumn('employee_defaults', 'unit_id')) {
                $table->dropConstrainedForeignId('unit_id');
            }
        });
    }
};
