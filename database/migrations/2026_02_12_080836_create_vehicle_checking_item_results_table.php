<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_checking_item_results', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('vehicle_checking_detail_id');
            $table->foreign('vehicle_checking_detail_id', 'fk_vcir_detail')
                  ->references('id')
                  ->on('vehicle_checking_details')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('vehicle_part_id');
            $table->foreign('vehicle_part_id', 'fk_vcir_part')
                  ->references('id')
                  ->on('vehicle_parts')
                  ->onDelete('restrict');

            $table->string('status', 20)->comment('Routine: ok/ng | Body: baik/cukup/kurang/tidak');
            $table->text('notes')->nullable()->comment('Keterangan per item');
            $table->boolean('needs_replacement')->default(false)->comment('Flag trigger permohonan penggantian');
            $table->timestamps();

            $table->index('vehicle_checking_detail_id', 'idx_vcir_detail');
            $table->index('vehicle_part_id', 'idx_vcir_part');
            $table->index('needs_replacement', 'idx_vcir_replacement');
            $table->index('status', 'idx_vcir_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_checking_item_results');
    }
};
