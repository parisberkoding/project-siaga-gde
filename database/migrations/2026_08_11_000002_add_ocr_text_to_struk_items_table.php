<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('struk_items', function (Blueprint $table) {
            $table->text('ocr_text')->nullable()->after('keterangan');
        });

        // Backfill: pindahkan keterangan panjang (kemungkinan teks OCR mentah)
        // ke ocr_text, lalu kosongkan keterangan. Keterangan ringkas (<=100
        // karakter, multi-line pendek) dianggap hasil edit/user.
        DB::table('struk_items')
            ->whereNotNull('keterangan')
            ->whereRaw('LENGTH(keterangan) > 100')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('struk_items')
                    ->where('id', $row->id)
                    ->update([
                        'ocr_text'   => $row->keterangan,
                        'keterangan' => null,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('struk_items', function (Blueprint $table) {
            $table->dropColumn('ocr_text');
        });
    }
};
