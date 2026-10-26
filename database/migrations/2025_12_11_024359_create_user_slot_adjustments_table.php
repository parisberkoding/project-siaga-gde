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
        Schema::create('user_slot_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['penalty', 'bonus']); // penalty = -1 slot, bonus = +1 slot
            $table->string('reason'); // 'voucher_expired', 'early_submission'
            $table->unsignedBigInteger('related_permohonan_id')->nullable();
            $table->datetime('effective_from'); // Mulai berlaku kapan
            $table->datetime('effective_until'); // Sampai kapan
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('related_permohonan_id')->references('id')->on('permohonans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_slot_adjustments');
    }
};
