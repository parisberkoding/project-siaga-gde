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
        Schema::create('surat_spd_extensions', function (Blueprint $table) {
            $table->id();

            $table->string('extension_number')->unique(); // Nomor perpanjangan surat SPD

            $table->unsignedBigInteger('original_surat_id'); // Surat SPD asli
            $table->unsignedBigInteger('extension_surat_id'); // Surat SPD perpanjangan
            $table->foreign('original_surat_id')->references('id')->on('surat_spds')->onDelete('cascade');
            $table->foreign('extension_surat_id')->references('id')->on('surat_spds')->onDelete('cascade');

            // Log perpanjangan
            $table->string('nomor_surat_extension'); // Nomor surat perpanjangan
            $table->date('tanggal_kembali_original'); // Tanggal kembali sebelum perpanjangan
            $table->date('tanggal_kembali_baru'); // Tanggal kembali setelah perpanjangan
            $table->integer('durasi_tambahan_hari'); // Berapa hari ditambah

            // Budget perpanjangan
            $table->decimal('budget_tambahan', 15, 2); // durasi_tambahan * budget_per_hari
            $table->decimal('total_penggunaan_uang', 15, 2)->nullable();

            // Approval & Acknowledgement
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_acknowledged')->default(false); // Superadmin checklist
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('acknowledgement_note')->nullable(); // Catatan superadmin

            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            $table->text('alasan_perpanjangan')->nullable(); // Dari driver
            $table->text('catatan')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('original_surat_id');
            $table->index('extension_surat_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spd_surat_extensions');
    }
};
