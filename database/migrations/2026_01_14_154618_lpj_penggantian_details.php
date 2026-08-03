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
        Schema::create('lpj_penggantian_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('lpj_penggantian_id');
            $table->foreign('lpj_penggantian_id')->references('id')->on('lpj_penggantians')->onDelete('cascade');

            // Tanggal Transaksi
            $table->date('tanggal');

            // Tipe Penggunaan & Nominal
            $table->enum('tipe_penggunaan', ['BBM', 'Tol','Parkir', 'Lain-lain']);
            $table->decimal('nominal', 15, 2)->default(0);

            // Keterangan tambahan
            $table->text('keterangan')->nullable();

            // Urutan (untuk sorting di table)
            $table->integer('urutan')->default(1);

            $table->timestamps();

            // Indexes
            $table->index('lpj_penggantian_id');
            $table->index('tanggal');
            $table->index('tipe_penggunaan');
            $table->index('urutan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lpj_penggantian_details');
    }
};
