<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_specifications', function (Blueprint $table) {
            $table->id();

            // FK ke vehicle_and_tools_consumers (hanya type KENDARAAN)
            $table->unsignedBigInteger('vehicle_and_tools_consumer_id')->unique();
            $table->foreign('vehicle_and_tools_consumer_id')
                  ->references('id')
                  ->on('vehicle_and_tools_consumers')
                  ->onDelete('cascade');

            // Kapasitas penumpang (tidak termasuk driver)
            $table->unsignedTinyInteger('passenger_capacity');

            // Jenis bahan bakar: Pertamax, Solar, Pertalite, dll.
            $table->string('fuel_type', 50);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_specifications');
    }
};
