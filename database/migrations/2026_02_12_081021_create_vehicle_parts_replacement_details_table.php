<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_parts_replacement_details', function (Blueprint $table) {
            $table->id();
            $table->string('vprd_code', 60)->unique()->comment('Slug: VPRD-001-001');

            $table->unsignedBigInteger('vehicle_parts_replacement_id');
            $table->foreign('vehicle_parts_replacement_id', 'fk_vprd_replacement')
                  ->references('id')
                  ->on('vehicle_parts_replacements')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('vehicle_part_id');
            $table->foreign('vehicle_part_id', 'fk_vprd_part')
                  ->references('id')
                  ->on('vehicle_parts')
                  ->onDelete('restrict');

            $table->string('item_name', 100)->comment('Denormalized nama item');
            $table->string('condition_status', 20)->comment('Status saat dicek: ng/kurang/tidak');
            $table->text('dispatcher_notes')->nullable()->comment('Catatan dispatcher tentang kondisi');
            $table->timestamps();

            $table->index('vehicle_parts_replacement_id', 'idx_vprd_replacement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_parts_replacement_details');
    }
};
