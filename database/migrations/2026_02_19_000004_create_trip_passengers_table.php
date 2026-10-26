<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_passengers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('trip_id');
            $table->foreign('trip_id')
                  ->references('id')
                  ->on('trips')
                  ->onDelete('cascade');

            // Penumpang yang booking
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            // Nomor kursi yang dipilih (1 s/d passenger_capacity)
            $table->unsignedTinyInteger('seat_number');

            // Tujuan masing-masing penumpang
            $table->enum('destination_type', ['PMU', 'Patuha', 'Basecamp', 'Direct_Use', 'Other']);
            $table->string('destination_label', 150)->nullable();

            // Status penumpang dalam trip
            $table->enum('boarding_status', ['waiting', 'onboard', 'completed'])->default('waiting');

            // Satu user hanya boleh punya 1 kursi per trip
            $table->unique(['trip_id', 'user_id']);
            // Satu kursi hanya boleh dipegang 1 orang per trip
            $table->unique(['trip_id', 'seat_number']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_passengers');
    }
};
