<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SuratSpd;

return new class extends Migration
{
    /**
     * Tambah kolom slug (URL-safe route key) ke surat_spds.
     * ID internal tidak lagi tampil di URL — diganti kode surat tanpa slash.
     */
    public function up(): void
    {
        Schema::table('surat_spds', function (Blueprint $table) {
            $table->string('slug', 191)->nullable()->after('id');
        });

        foreach (SuratSpd::withTrashed()->cursor() as $surat) {
            $surat->slug = str_replace('/', '-', $surat->nomor_surat);
            $surat->timestamps = false;
            $surat->saveQuietly();
        }

        Schema::table('surat_spds', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('surat_spds', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
