<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pekerja_patuha', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pekerja', 50)->unique();
            $table->string('nama_lengkap', 150);
            $table->enum('default_location', ['PMU', 'Patuha']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pekerja_patuha');
    }
};
