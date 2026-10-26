<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_parts_replacements', function (Blueprint $table) {
            $table->id();
            $table->string('vpr_code', 60)->unique()->comment('Slug: VPR-D1234AB-2025-02-001');

            $table->unsignedBigInteger('vehicle_checking_detail_id')->comment('Dari pengecekan mana');
            $table->foreign('vehicle_checking_detail_id', 'fk_vpr_checking_detail')
                  ->references('id')
                  ->on('vehicle_checking_details')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('vehicle_id');
            $table->foreign('vehicle_id', 'fk_vpr_vehicle')
                  ->references('id')
                  ->on('vehicle_and_tools_consumers')
                  ->onDelete('restrict');

            $table->string('nopol', 20)->comment('Denormalized');

            $table->unsignedBigInteger('dispatcher_id')->comment('Yang mengajukan permohonan');
            $table->foreign('dispatcher_id', 'fk_vpr_dispatcher')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('last_driver_id')->comment('Driver terakhir bawa kendaraan');
            $table->foreign('last_driver_id', 'fk_vpr_last_driver')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('desired_driver_id')->nullable()->comment('Driver yang akan bawa ke service');
            $table->foreign('desired_driver_id', 'fk_vpr_desired_driver')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->unsignedBigInteger('approved_by')->nullable()->comment('Admin/Superadmin GA yang approve');
            $table->foreign('approved_by', 'fk_vpr_approver')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'scheduled',
                'in_service',
                'completed'
            ])->default('pending');

            $table->date('scheduled_date')->nullable()->comment('Jadwal service, diisi admin/superadmin');
            $table->string('service_location', 255)->nullable()->comment('Tempat service');
            $table->text('rejection_reason')->nullable()->comment('Alasan jika ditolak');

            $table->datetime('approved_at')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->datetime('scheduled_at')->nullable()->comment('Kapan jadwal dirilis');
            $table->datetime('completed_at')->nullable()->comment('Kapan service selesai');
            $table->timestamps();

            $table->index('vehicle_id');
            $table->index('dispatcher_id');
            $table->index('status');
            $table->index('scheduled_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_parts_replacements');
    }
};
