<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\StrukItem;

return new class extends Migration
{
    /**
     * Tambah kolom slug (URL-safe route key) ke struk_items.
     * item_code berisi slash (menyalin nomor surat) sehingga tidak aman
     * dipakai langsung sebagai route segment. Slug acak singkat dibuat
     * untuk endpoint AJAX item laporan harian.
     */
    public function up(): void
    {
        Schema::table('struk_items', function (Blueprint $table) {
            $table->string('slug', 191)->nullable()->after('id');
        });

        foreach (StrukItem::cursor() as $item) {
            $item->slug = 'ITM-' . strtoupper(bin2hex(random_bytes(5)));
            $item->timestamps = false;
            $item->saveQuietly();
        }

        Schema::table('struk_items', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('struk_items', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
