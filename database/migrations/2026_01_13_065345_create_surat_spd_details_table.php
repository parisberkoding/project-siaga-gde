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
        Schema::create('spd_surat_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('surat_spd_id');
            $table->foreign('surat_spd_id')->references('id')->on('surat_spds')->onDelete('cascade');

            // Nama Lengkap Penumpang
            $table->string('nama_lengkap');
            $table->integer('urutan')->default(1); // Urutan di list (1-7 atau lebih)

            $table->timestamps();

            // Indexes
            $table->index('surat_spd_id');
            $table->index('urutan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spd_surat_details');
    }
};
