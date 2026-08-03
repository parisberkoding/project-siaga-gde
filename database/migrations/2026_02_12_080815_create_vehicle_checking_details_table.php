<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_checking_details', function (Blueprint $table) {
            $table->id();
            $table->string('checking_detail_code', 60)->unique()->comment('Slug: CHK-D1234AB-2025-02-001');

            $table->unsignedBigInteger('vehicle_checking_id');
            $table->foreign('vehicle_checking_id')
                  ->references('id')
                  ->on('vehicle_checkings')
                  ->onDelete('cascade');

            $table->enum('type', ['routine', 'body'])->comment('Jenis pengecekan');
            $table->enum('sub_type', ['daily', 'weekly', 'monthly'])->nullable()->comment('Khusus routine, null untuk body');

            $table->unsignedBigInteger('dispatcher_id')->comment('Yang melakukan pengecekan');
            $table->foreign('dispatcher_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('current_driver_id')->comment('Driver yang bawa kendaraan saat itu');
            $table->foreign('current_driver_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            $table->datetime('checked_at')->comment('Waktu pengecekan dilakukan');
            $table->boolean('driver_acknowledged')->default(false);
            $table->datetime('driver_acknowledged_at')->nullable();
            $table->text('notes')->nullable()->comment('Catatan umum pengecekan');
            $table->timestamps();

            $table->index('vehicle_checking_id');
            $table->index('dispatcher_id');
            $table->index('current_driver_id');
            $table->index('type');
            $table->index('driver_acknowledged');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_checking_details');
    }
};
