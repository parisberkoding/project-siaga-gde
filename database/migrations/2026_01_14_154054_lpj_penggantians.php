<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lpj_penggantians', function (Blueprint $table) {
            $table->id();

            $table->string('lpj_number')->unique(); // Nomor LPJ Penggantian

            // Reference ke Surat SPD
            $table->unsignedBigInteger('surat_spd_id');
            $table->foreign('surat_spd_id')->references('id')->on('surat_spds')->onDelete('cascade');

            // Informasi Pemohon
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->string('nama');
            $table->string('jabatan');

            // Kendaraan
            $table->unsignedBigInteger('vehicle_id');
            $table->foreign('vehicle_id')->references('id')->on('vehicle_and_tools_consumers')->onDelete('restrict');
            $table->string('nomor_polisi'); // Cached from vehicle

            // Uraian Perjalanan
            $table->text('uraian');

            // Tanggal LPJ & Due Date
            $table->date('tanggal_lpj')->nullable();
            $table->date('due_date')->nullable(); // 3 hari setelah tanggal kembali (dari SPD atau extension)
            $table->boolean('is_late')->default(false); // Flag jika terlambat submit
            $table->integer('days_late')->default(0); // Berapa hari telat

            // Total Biaya (calculated from details)
            $table->decimal('total_biaya_tol', 15, 2)->default(0);
            $table->decimal('total_biaya_portir', 15, 2)->default(0);
            $table->decimal('total_biaya_bbm', 15, 2)->default(0);
            $table->decimal('total_biaya_lain', 15, 2)->default(0);
            $table->decimal('total_pengeluaran', 15, 2)->default(0); // Sum of all

            // Informasi Keuangan
            $table->enum('uang_dari', ['KAS', 'Transfer'])->default('KAS');
            $table->decimal('jumlah_diberikan', 15, 2)->default(0);
            $table->decimal('pengembalian', 15, 2)->default(0); // If positive
            $table->decimal('kekurangan', 15, 2)->default(0); // If negative

            // Validation Budget per Hari
            $table->decimal('budget_per_hari', 15, 2)->default(1400000);
            $table->boolean('is_over_budget')->default(false); // Flag jika melebihi budget
            $table->text('over_budget_reason')->nullable(); // Alasan jika over budget

            // Catatan
            $table->text('catatan')->nullable();

            // Approval Flow
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Multiple approvers
            $table->foreignId('approved_by_pengguna')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_pengguna_at')->nullable();

            $table->foreignId('approved_by_divisi_ga')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_divisi_ga_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('surat_spd_id');
            $table->index('user_id');
            $table->index('vehicle_id');
            $table->index('status');
            $table->index('is_over_budget');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lpj_penggantians');
    }
};
