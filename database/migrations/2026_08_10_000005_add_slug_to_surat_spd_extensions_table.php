<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SuratSpdExtension;

return new class extends Migration
{
    /**
     * Tambah kolom slug (URL-safe route key) ke surat_spd_extensions.
     * nomor_surat_extension berisi slash sehingga tidak aman untuk URL.
     */
    public function up(): void
    {
        Schema::table('surat_spd_extensions', function (Blueprint $table) {
            $table->string('slug', 191)->nullable()->after('id');
        });

        foreach (SuratSpdExtension::cursor() as $ext) {
            $ext->slug = str_replace('/', '-', $ext->nomor_surat_extension);
            $ext->timestamps = false;
            $ext->saveQuietly();
        }

        Schema::table('surat_spd_extensions', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('surat_spd_extensions', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
