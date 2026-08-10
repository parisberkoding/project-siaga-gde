<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * struk_items — rincian per-item pengeluaran SPD (BBM/Tol/Parkir/Lainnya)
     * yang wajib membawa bukti gambar struk.
     *
     * 1 LPJ/SPD = banyak struk_items. Setiap item di-OCR (AI), dikategorikan,
     * lalu driver boleh mengoreksi. DB hanya menyimpan PATH gambar, bukan base64.
     */
    public function up(): void
    {
        Schema::create('struk_items', function (Blueprint $table) {
            $table->id();

            // Surat SPD asal (denormalisasi nomor surat agar mudah query/report)
            $table->unsignedBigInteger('spd_id');
            $table->foreign('spd_id')->references('id')->on('surat_spds')->onDelete('cascade');
            $table->string('nomor_surat_spd', 100)->nullable();

            // Kait ke LPJ — nullable sampai LPJ dibuat
            $table->unsignedBigInteger('lpj_id')->nullable();
            $table->foreign('lpj_id')->references('id')->on('lpj_penggantians')->onDelete('cascade');

            $table->unsignedBigInteger('lpj_detail_id')->nullable();
            $table->foreign('lpj_detail_id')->references('id')->on('lpj_penggantian_details')->onDelete('set null');

            // Kode unik item — format: S.{KATEGORI}-[Nomer Surat SPD]-DD-MM-YYYY-[20 digit]
            $table->string('item_code')->unique();

            // Bukti struk: hanya PATH, tidak menyimpan base64 di DB
            $table->string('path_image');

            // Kategori hasil AI (bisa dikoreksi driver) + sumber kategori
            $table->enum('struk_kategori', ['BBM', 'Tol', 'Parkir', 'Lainnya'])->default('Lainnya');
            $table->enum('kategori_source', ['ai', 'manual'])->default('ai');

            // Hasil OCR mentah (JSON) — metadata yang di-prefill, tetap bisa diedit
            $table->text('metadata_json')->nullable();

            // Field hasil edit driver
            $table->decimal('nominal', 15, 2)->default(0);
            $table->date('tanggal')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('urutan')->default(1);

            $table->timestamps();

            $table->index('spd_id');
            $table->index('lpj_id');
            $table->index('struk_kategori');
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('struk_items');
    }
};
