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
        Schema::create('vehicle_barcodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_and_tools_consumer_id')->unique();
            $table->string('barcode_path'); // relative path: barcode-vehicle/D_1077_ALV.jpg
            $table->timestamps();

            $table->foreign('vehicle_and_tools_consumer_id')
                  ->references('id')
                  ->on('vehicle_and_tools_consumers')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_barcodes');
    }
};
