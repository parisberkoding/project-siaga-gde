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
        Schema::create('spd_barcode_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spd_id');
            $table->foreign('spd_id')->references('id')->on('surat_spds')->onDelete('cascade');
            $table->string('signature_path');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spd_barcode_signatures');
    }
};
