<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_checking_detail_id');
            $table->string('photo'); // storage path
            $table->string('zone')->default('depan'); // depan, belakang, kiri, kanan, atas, bawah, interior
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('vehicle_checking_detail_id')
                  ->references('id')
                  ->on('vehicle_checking_details')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_photos');
    }
};
