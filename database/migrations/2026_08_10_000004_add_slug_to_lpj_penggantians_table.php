<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\LpjPenggantian;

return new class extends Migration
{
    /**
     * Tambah kolom slug (URL-safe route key) ke lpj_penggantians.
     * lpj_number berisi slash ("PTH.01/LPJ/SPD/...") sehingga tidak aman
     * dipakai langsung sebagai route segment.
     */
    public function up(): void
    {
        Schema::table('lpj_penggantians', function (Blueprint $table) {
            $table->string('slug', 191)->nullable()->after('id');
        });

        foreach (LpjPenggantian::withTrashed()->cursor() as $lpj) {
            $lpj->slug = str_replace('/', '-', $lpj->lpj_number);
            $lpj->timestamps = false;
            $lpj->saveQuietly();
        }

        Schema::table('lpj_penggantians', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('lpj_penggantians', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
