<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_and_tools_consumers', function (Blueprint $table) {
            $table->id();
            $table->string('consumerial_type');
            $table->string('consumerial_code')->unique();
            $table->string('consumerial_name');
            $table->string('consumerial_notes')->nullable();
            $table->integer('optimum_jarak_per_liter')->nullable(); # parameter untuk patokan efisiensi bahan bakar
            $table->string('satuan')->nullable(); #km, jam, dll
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_and_tools_consumers');
    }
};
