<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master harga BBM (PERTAMAX & PERTAMINA DEX) per region/provinsi.
     * Disinkronkan harian dari bensin-api (GitHub Pages).
     * Sumber acuan perhitungan benchmark per kendaraan.
     */
    public function up(): void
    {
        Schema::create('master_gasolines', function (Blueprint $table) {
            $table->id();
            $table->string('province', 100)->comment('Nama provinsi, contoh: Prov. Jawa Barat');
            $table->string('province_slug', 100)->comment('Slug provinsi, contoh: jawa-barat');
            $table->string('fuel_type', 100)->comment('Produk BBM: PERTAMAX / PERTAMINA DEX');
            $table->unsignedInteger('price_per_liter')->comment('Harga per liter dalam rupiah');
            $table->string('availability', 30)->nullable()->comment('Status ketersediaan dari API');
            $table->timestamp('price_date')->nullable()->comment('Tanggal update harga dari Pertamina (pertamina_updated_at)');
            $table->timestamp('synced_at')->nullable()->comment('Waktu sinkronisasi terakhir dari API');
            $table->timestamps();

            $table->unique(['province_slug', 'fuel_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_gasolines');
    }
};
