<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            // Jadwal driver yang menjadi dasar trip ini
            $table->unsignedBigInteger('driver_schedule_id');
            $table->foreign('driver_schedule_id')
                  ->references('id')
                  ->on('driver_schedules')
                  ->onDelete('restrict');

            // Titik keberangkatan (default dari config siaga.trip_origin)
            $table->string('origin', 100);

            // Tujuan utama driver pada trip ini
            $table->enum('destination_type', ['PMU', 'Patuha', 'Basecamp', 'Direct_Use', 'Other']);

            // Diisi manual jika destination_type = Other
            $table->string('destination_label', 150)->nullable();

            // Waktu mulai dan selesai trip (diisi driver via tombol)
            $table->timestamp('start_trip_at')->nullable();
            $table->timestamp('end_trip_at')->nullable();

            $table->enum('status', ['scheduled', 'ongoing', 'completed'])->default('scheduled');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
