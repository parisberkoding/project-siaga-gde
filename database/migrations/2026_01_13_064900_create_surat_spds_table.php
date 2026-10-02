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
        Schema::create('surat_spds', function (Blueprint $table) {
            $table->id();

            $table->string('nomor_surat', 100)->unique();
            $table->string('nomor_urut')->nullable();

            $table->unsignedBigInteger('unit_id')->nullable();
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');

            // Pemberi Tugas
            $table->string('pemberi_tugas_nama');
            $table->string('pemberi_tugas_jabatan');

            // Pengemudi (requester)
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->string('pengemudi_nama');

            // Informasi Perjalanan
            $table->text('tempat_tujuan');
            $table->string('melayani_bidang');
            $table->integer('jumlah_pemakai')->default(1);

            // Kendaraan
            $table->unsignedBigInteger('vehicle_id');
            $table->foreign('vehicle_id')->references('id')->on('vehicle_and_tools_consumers')->onDelete('restrict');

            // Jadwal
            $table->date('tanggal_berangkat');
            $table->date('tanggal_kembali');
            $table->integer('lama_perjalanan_hari')->default(1); // Calculated field

            // Budget Calculation (1.4 juta per hari)
            $table->decimal('total_budget', 15, 2)->nullable(); // lama_perjalanan * budget_per_hari

            // Extended Time Duty (ETD) Support
            $table->unsignedBigInteger('parent_surat_id')->nullable(); // Jika ini perpanjangan dari surat lain
            $table->foreign('parent_surat_id')->references('id')->on('surat_spds')->onDelete('set null');
            $table->boolean('is_extension')->default(false);
            $table->datetime('lpj_deadline')->nullable(); // Deadline for LPJ submission
            $table->integer('extension_count')->default(0); // Berapa kali sudah diperpanjang

            // Catatan & Metadata
            $table->text('catatan')->nullable();
            $table->string('tanggal_surat')->nullable(); // Format: "Patuha, 23 Juni 2025"

            // Approval Flow
            $table->enum('status', ['pending', 'approved', 'rejected', 'extended'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('user_id');
            $table->index('vehicle_id');
            $table->index('parent_surat_id');
            $table->index('tanggal_berangkat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_spds');
    }
};
