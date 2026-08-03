<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_parts_replacement_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('vprt_code', 60)->unique()->comment('Slug: VPRT-D1234AB-2025-02-001');

            $table->unsignedBigInteger('vehicle_parts_replacement_id');
            $table->foreign('vehicle_parts_replacement_id', 'fk_vprt_replacement')
                  ->references('id')
                  ->on('vehicle_parts_replacements')
                  ->onDelete('restrict');

            $table->string('vpr_code', 60)->comment('Denormalized VPR code');

            $table->unsignedBigInteger('driver_id')->comment('Driver yang bawa ke service');
            $table->foreign('driver_id', 'fk_vprt_driver')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            $table->string('service_location', 255)->comment('Nama bengkel/tempat service');
            $table->date('service_date')->comment('Tanggal service dilakukan');
            $table->decimal('total_amount', 15, 2)->comment('Total biaya transaksi');
            $table->string('proof_photo', 255)->comment('Path file JPEG struk/nota/invoice');

            $table->date('deadline_date')->comment('Auto: service_date + 3 hari');
            $table->datetime('reported_at')->nullable()->comment('Kapan driver submit laporan');
            $table->boolean('is_overdue')->default(false)->comment('True jika reported_at > deadline_date');
            $table->timestamps();

            $table->index('vehicle_parts_replacement_id', 'idx_vprt_replacement');
            $table->index('driver_id');
            $table->index('service_date');
            $table->index('is_overdue');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_parts_replacement_transactions');
    }
};
