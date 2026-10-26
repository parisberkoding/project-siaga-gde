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
        Schema::create('vehicle_fuel_baselines', function (Blueprint $table) {
            $table->id();

            // ==================== FOREIGN KEY ====================
            $table->foreignId('vehicle_id')
                ->constrained('vehicle_and_tools_consumers')
                ->onDelete('cascade')
                ->comment('ID Kendaraan');

            // ==================== BASELINE DATA ====================
            $table->string('fuel_type', 50)
                ->comment('Jenis BBM (Pertamax/Pertamina Dex/Pertalite)');

            $table->decimal('optimal_efficiency_km_per_liter', 5, 2)
                ->comment('Efisiensi optimal dalam KM per Liter (contoh: 15.50)');

            // ==================== CALCULATION METHOD ====================
            $table->enum('calculation_method', ['manual', 'auto_calculated', 'admin_override'])
                ->default('manual')
                ->comment('Cara perhitungan baseline: manual/auto/admin_override');

            $table->integer('sample_size')->nullable()
                ->comment('Jumlah trip untuk auto-calculated (contoh: 20 trip terakhir)');

            // ==================== METADATA ====================
            $table->timestamp('last_calibrated_at')->nullable()
                ->comment('Waktu terakhir dikalibrasi');

            $table->foreignId('calibrated_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('User yang melakukan kalibrasi');

            $table->text('notes')->nullable()
                ->comment('Catatan tambahan tentang baseline ini');

            $table->timestamps();
            $table->softDeletes();

            // ==================== INDEXES ====================
            $table->index('vehicle_id', 'idx_baseline_vehicle');
            $table->index('fuel_type', 'idx_baseline_fuel_type');
            $table->index('calculation_method', 'idx_baseline_calc_method');

            // ==================== UNIQUE CONSTRAINT ====================
            // One baseline per vehicle per fuel type
            $table->unique(['vehicle_id', 'fuel_type'], 'uk_baseline_vehicle_fuel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_fuel_baselines');
    }
};
