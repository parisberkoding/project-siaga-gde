<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catering_guests', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('instansi', 200);           // Nama tamu / instansi
            $table->unsignedSmallInteger('jumlah');    // Jumlah orang
            $table->string('site', 20)->default('PMU'); // Site tujuan: PMU atau Patuha
            $table->text('catatan')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catering_guests');
    }
};
