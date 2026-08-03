<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_schedules', function (Blueprint $table) {
            $table->id();

            // Driver yang dijadwalkan (role = requester)
            $table->unsignedBigInteger('driver_id');
            $table->foreign('driver_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            // Kendaraan yang dijadwalkan (type = KENDARAAN)
            $table->unsignedBigInteger('vehicle_consumer_id');
            $table->foreign('vehicle_consumer_id')
                  ->references('id')
                  ->on('vehicle_and_tools_consumers')
                  ->onDelete('restrict');

            $table->date('start_date');
            $table->date('end_date');

            // Siapa yang membuat jadwal (admin / superadmin)
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            // Siapa yang approve (superadmin, nullable)
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // Status alur persetujuan + self-assign
            $table->enum('status', ['pending', 'approved', 'rejected', 'self_assigned'])
                  ->default('pending');

            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_schedules');
    }
};
