<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lunch Transaction = Ground Truth Lunch Management V2.
     *
     * Satu baris = satu kehadiran makan siang yang tercatat lewat QR
     * Attendance (public, tanpa login). Menggantikan peran trip/vehicle
     * tracking sebagai sumber "actual" — kendaraan tidak lagi menjadi
     * bukti seseorang makan di site.
     *
     * Dua lapis data sesuai kebutuhan Identity Resolution:
     *  - raw_*      : persis seperti yang diketik user → audit trail saja,
     *                 TIDAK pernah dipakai sebagai data utama.
     *  - kolom hasil: employee_default_id + snapshot nama/divisi/posisi
     *                 hasil lookup master → inilah primary data.
     *  - metadata   : confidence_score + resolution_method untuk
     *                 mengevaluasi kualitas engine.
     *
     * tomorrow_site_id menyimpan jawaban popup "Besok makan dimana?" dan
     * menjadi input prioritas ke-2 Forecast H+1.
     */
    public function up(): void
    {
        Schema::create('lunch_transactions', function (Blueprint $table) {
            $table->id();

            $table->date('transaction_date');

            $table->foreignId('site_id')->constrained('sites')->restrictOnDelete();

            // Hasil resolve (primary data)
            $table->foreignId('employee_default_id')->nullable()
                ->constrained('employee_defaults')->nullOnDelete();
            $table->string('employee_name', 150)->nullable();
            $table->string('division', 100)->nullable();
            $table->string('position', 100)->nullable();

            // Raw input (audit trail)
            $table->string('raw_alias', 150);
            $table->string('raw_position', 100)->nullable();
            $table->string('raw_division', 100)->nullable();

            // Metadata resolusi
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->string('resolution_method', 30)->default('unresolved');
            // alias_exact | name_exact | historical | fuzzy | manual | unresolved

            // Jawaban popup "Besok makan dimana?" (nullable = Lewati)
            $table->foreignId('tomorrow_site_id')->nullable()
                ->constrained('sites')->nullOnDelete();

            // Token acak yang dikembalikan saat submit; wajib cocok saat
            // konfirmasi besok — mencegah orang lain mengubah jawaban
            // transaksi milik orang lain di halaman publik.
            $table->string('confirmation_token', 64)->nullable();

            $table->string('source', 20)->default('qr');
            $table->timestamp('scanned_at')->nullable();

            $table->timestamps();

            // Satu karyawan hanya tercatat sekali per hari; baris
            // unresolved (employee NULL) tidak dibatasi oleh unique ini.
            $table->unique(['employee_default_id', 'transaction_date']);
            $table->index(['transaction_date', 'site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lunch_transactions');
    }
};
