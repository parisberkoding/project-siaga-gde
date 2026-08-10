<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SpdDailyReport;

return new class extends Migration
{
    /**
     * Tambah kolom slug (URL-safe route key) ke spd_daily_reports.
     * Laporan harian tidak punya kode alami, jadi dibuat slug acak singkat.
     */
    public function up(): void
    {
        Schema::table('spd_daily_reports', function (Blueprint $table) {
            $table->string('slug', 191)->nullable()->after('id');
        });

        foreach (SpdDailyReport::cursor() as $daily) {
            $daily->slug = 'LHP-' . strtoupper(bin2hex(random_bytes(5)));
            $daily->timestamps = false;
            $daily->saveQuietly();
        }

        Schema::table('spd_daily_reports', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('spd_daily_reports', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
