<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_spds', function (Blueprint $table) {
            $table->string('pemberi_tugas_nama')->nullable()->change();
            $table->string('pemberi_tugas_jabatan')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('surat_spds', function (Blueprint $table) {
            $table->string('pemberi_tugas_nama')->nullable(false)->change();
            $table->string('pemberi_tugas_jabatan')->nullable(false)->change();
        });
    }
};