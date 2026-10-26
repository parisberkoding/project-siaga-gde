<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Learning Engine profile: agregat kebiasaan makan per karyawan per
     * site, dibangun HANYA dari lunch_transactions (ground truth aktual).
     * Forecast tidak pernah menjadi sumber learning — kalau forecast ikut
     * dipelajari, kesalahan prediksi akan menguatkan dirinya sendiri.
     *
     * Counter sederhana per (karyawan, site) cukup untuk prioritas
     * "Historical Pattern" pada Forecast H+1: site dengan transaksi
     * terbanyak = prediksi, share-nya = confidence.
     */
    public function up(): void
    {
        Schema::create('learning_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_default_id')
                ->constrained('employee_defaults')->cascadeOnDelete();

            $table->foreignId('site_id')
                ->constrained('sites')->cascadeOnDelete();

            $table->unsignedInteger('transaction_count')->default(0);
            $table->date('last_transaction_date')->nullable();

            $table->timestamps();

            $table->unique(['employee_default_id', 'site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_profiles');
    }
};
