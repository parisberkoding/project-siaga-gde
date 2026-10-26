<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_code', 30)->unique()->comment('Slug URL: PRT-MESIN, PRT-HEAD-LAMP');
            $table->string('name', 100)->comment('Nama item: Mesin, Head Lamp, dll');
            $table->enum('category', ['kesiapan', 'keselamatan', 'kelengkapan', 'body'])->comment('Grup kategori');
            $table->enum('checking_group', ['routine', 'body'])->comment('Filter form: routine atau body');
            $table->enum('check_frequency', ['daily', 'weekly', 'monthly'])->comment('Frekuensi pengecekan');
            $table->enum('status_type', ['binary', 'scale'])->comment('binary=V/X, scale=Baik/Cukup/Kurang/Tidak');
            $table->string('standard_description', 255)->nullable()->comment('Standar: Normal, Sesuai Level, dll');
            $table->integer('sort_order')->default(0)->comment('Urutan tampil di form');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('checking_group');
            $table->index('check_frequency');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_parts');
    }
};
