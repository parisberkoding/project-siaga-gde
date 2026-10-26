<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laporan harian SPD (pre-fixed report).
     *
     * Alur baru: driver mengirim laporan per hari selama perjalanan dinas
     * (status draft -> submitted). Setelah tanggal selesai SPD lewat, seluruh
     * laporan harian di-validasi lalu digabung menjadi satu LPJ final
     * (fixed report) yang direview admin GA & dimuat ke template cetak.
     */
    public function up(): void
    {
        Schema::create('spd_daily_reports', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('surat_spd_id');
            $table->foreign('surat_spd_id')->references('id')->on('surat_spds')->onDelete('cascade');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->date('tanggal');
            $table->text('uraian')->nullable();
            $table->text('catatan')->nullable();

            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            $table->index(['surat_spd_id', 'status']);
            $table->index('tanggal');
        });

        Schema::table('struk_items', function (Blueprint $table) {
            $table->unsignedBigInteger('spd_daily_report_id')->nullable()->after('spd_id');
            $table->foreign('spd_daily_report_id')->references('id')->on('spd_daily_reports')->onDelete('cascade');
            $table->index('spd_daily_report_id');
        });
    }

    public function down(): void
    {
        Schema::table('struk_items', function (Blueprint $table) {
            $table->dropForeign(['spd_daily_report_id']);
            $table->dropIndex('struk_items_spd_daily_report_id_index');
            $table->dropColumn('spd_daily_report_id');
        });

        Schema::dropIfExists('spd_daily_reports');
    }
};
