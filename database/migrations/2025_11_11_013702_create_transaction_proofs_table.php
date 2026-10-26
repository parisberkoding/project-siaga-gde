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
        Schema::create('transaction_proofs', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id');

            $table->unsignedBigInteger('req_id');
            $table->foreign('req_id')->references('id')->on('permohonans')->ondelete('restrict');

            $table->unsignedBigInteger('requester_id');
            $table->foreign('requester_id')->references('id')->on('users')->onDelete('restrict');

            $table->string('voucher_number')->nullable();

            $table->datetime('purchase_datetime');
            $table->integer('fuel_volume');
            $table->integer('km_terakhir')->nullable();
            $table->string('struk_bbm_path');
            $table->string('odometer_photo_path')->nullable();

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction__proofs');
    }
};
