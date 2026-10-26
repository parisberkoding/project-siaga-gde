<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_checkings', function (Blueprint $table) {
            $table->id();
            $table->string('checking_code', 50)->unique()->comment('Slug: CHK-D1234AB-2025-02');

            $table->unsignedBigInteger('vehicle_id');
            $table->foreign('vehicle_id')
                  ->references('id')
                  ->on('vehicle_and_tools_consumers')
                  ->onDelete('cascade');

            $table->string('nopol', 20)->comment('Denormalized dari vehicle consumerial_code');
            $table->integer('revision')->default(0)->comment('Counter revisi periode');
            $table->date('period_start')->comment('Tanggal 1 bulan berjalan');
            $table->date('period_end')->comment('Tanggal akhir bulan berjalan');

            $table->unsignedBigInteger('generated_by')->comment('Dispatcher yang generate');
            $table->foreign('generated_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamps();

            // Unique: 1 header per kendaraan per periode
            $table->unique(['vehicle_id', 'period_start'], 'uk_vehicle_period');
            $table->index('nopol');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_checkings');
    }
};
