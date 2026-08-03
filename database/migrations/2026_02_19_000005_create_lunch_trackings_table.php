<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lunch_trackings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('trip_id');
            $table->foreign('trip_id')
                  ->references('id')
                  ->on('trips')
                  ->onDelete('cascade');

            // Orang yang terlacak (driver maupun penumpang)
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            // Tanggal makan siang (diambil dari date(end_trip_at))
            $table->date('lunch_date');

            // Lokasi makan yang terkalkulasi
            $table->enum('lunch_location_type', ['site_pmu', 'site_patuha', 'external', 'none']);

            // Apakah masuk hitungan catering
            $table->boolean('is_catering_counted')->default(false);

            // Satu user hanya dihitung sekali per trip
            $table->unique(['trip_id', 'user_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lunch_trackings');
    }
};
