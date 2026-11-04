<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber baris pemberian dana SPD.
 *
 * Perpanjangan (ETD) tidak lagi otomatis menambah budget. Admin GA mencatat
 * sendiri kapan dana tambahan diberikan, sehingga baris pemberian dana perlu
 * menyimpan sumbernya (`awal`, `tambahan`, `perpanjangan`) dan — bila berasal
 * dari perpanjangan — record perpanjangan yang menjadi dasarnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_spd_budgets', function (Blueprint $table) {
            $table->string('sumber', 20)->default('awal')->after('catatan')
                ->comment('Sumber dana: awal, tambahan, perpanjangan');
            $table->foreignId('surat_spd_extension_id')
                ->nullable()
                ->after('sumber')
                ->constrained('surat_spd_extensions')
                ->nullOnDelete();

            $table->index(['surat_spd_id', 'sumber'], 'spd_budgets_surat_sumber_index');
        });
    }

    public function down(): void
    {
        Schema::table('surat_spd_budgets', function (Blueprint $table) {
            $table->dropIndex('spd_budgets_surat_sumber_index');
            $table->dropConstrainedForeignId('surat_spd_extension_id');
            $table->dropColumn('sumber');
        });
    }
};
