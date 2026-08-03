<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Lokasi default user untuk keperluan forecast catering SIAGA.
            // Null = belum diset / tidak relevan (misal: admin, dispatcher).
            // Digunakan LunchTrackingController::forecastForDate() ketika user
            // tidak punya trip di tanggal tersebut.
            $table->enum('default_location', ['PMU', 'Patuha'])
                  ->nullable()
                  ->default(null)
                  ->after('jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('default_location');
        });
    }
};
