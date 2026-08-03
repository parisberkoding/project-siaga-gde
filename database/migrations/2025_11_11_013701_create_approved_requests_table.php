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
        Schema::create('approved_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permohonan_id');
            $table->foreign('permohonan_id')->references('id')->on('permohonans')->onDelete('cascade');

            $table->timestamp('approval_date')->useCurrent();
            $table->string('voucher_code')->unique();
            $table->datetime('valid_until');
            $table->float('approved_amount');
            // hanya menyimpan "approved" & "completed"
            $table->string('status');
            $table->unsignedBigInteger('authorizer_id');
            $table->foreign('authorizer_id')->references('id')->on('users')->onDelete('restrict');

            $table->text('approval_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approved__requests');
    }
};
