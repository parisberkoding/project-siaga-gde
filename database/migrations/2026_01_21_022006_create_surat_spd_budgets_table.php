<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table untuk menyimpan budget per tanggal perjalanan
     * Setiap tanggal bisa punya nominal dan catatan berbeda
     */
    public function up(): void
    {
        Schema::create('surat_spd_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_spd_id')->constrained('surat_spds')->onDelete('cascade');
            $table->date('tanggal');
            $table->decimal('nominal', 15, 2)->default(0);
            $table->string('catatan')->nullable();
            $table->timestamps();

            // Index untuk query
            $table->index(['surat_spd_id', 'tanggal']);
        });

        // Hapus kolom budget_per_hari dari surat_spds jika sudah tidak diperlukan
        // Atau biarkan sebagai fallback/legacy
        // Schema::table('surat_spds', function (Blueprint $table) {
        //     $table->dropColumn('budget_per_hari');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_spd_budgets');
    }
};
