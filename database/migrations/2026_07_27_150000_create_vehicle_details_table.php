<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_and_tools_consumer_id')->unique();
            $table->foreign('vehicle_and_tools_consumer_id', 'fk_vd_consumer')
                  ->references('id')->on('vehicle_and_tools_consumers')
                  ->onDelete('cascade');

            $table->string('jenis_kendaraan')->nullable();
            $table->string('merk')->nullable();
            $table->string('model')->nullable();
            $table->year('tahun_pembuatan')->nullable();
            $table->string('nomor_rangka')->nullable();
            $table->string('nomor_mesin')->nullable();
            $table->string('warna')->nullable();
            $table->unsignedTinyInteger('kapasitas_penumpang')->nullable();
            $table->decimal('kapasitas_bahan_bakar', 8, 2)->nullable()->comment('liter');
            $table->string('jenis_bahan_bakar')->nullable();
            $table->decimal('volume_tangki', 8, 2)->nullable()->comment('liter');
            $table->string('foto')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_details');
    }
};
