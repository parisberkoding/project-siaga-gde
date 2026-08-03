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
        Schema::create('trip_validations', function (Blueprint $table) {
            $table->id();

            // ==================== LINK KE TRANSACTION PROOFS ====================
            $table->unsignedBigInteger('transaction_proof_previous_id')
                ->comment('Transaction Proof A (Sebelumnya)');
            $table->unsignedBigInteger('transaction_proof_latest_id')
                ->comment('Transaction Proof B (Terbaru)');

            // ==================== ODO DATA ====================
            $table->integer('previous_odo')->comment('Odometer dari transaksi sebelumnya');
            $table->integer('latest_odo')->comment('Odometer dari transaksi terbaru');
            $table->integer('diff_odo')->comment('Selisih KM (latest - previous)');

            // ==================== FUEL & COST DATA ====================
            $table->string('fuel_type', 50)->nullable()
                ->comment('Jenis BBM (Pertamax/Pertamina Dex/Pertalite)');
            $table->decimal('fuel_volume', 8, 2)->nullable()
                ->comment('Volume BBM yang diisi (Liter)');
            $table->decimal('fuel_cost', 12, 2)->nullable()
                ->comment('Biaya BBM (Rupiah)');

            // ==================== EFFICIENCY ANALYSIS ====================
            $table->decimal('actual_efficiency', 5, 2)->nullable()
                ->comment('Efisiensi aktual (KM per Liter) = diff_odo / fuel_volume');
            $table->decimal('expected_efficiency', 5, 2)->nullable()
                ->comment('Efisiensi optimal dari baseline (KM per Liter)');
            $table->decimal('efficiency_deviation_percent', 6, 2)->nullable()
                ->comment('Deviasi efisiensi dalam persen (%)');

            // ✅ NEW: BASELINE OVERRIDE (Penyesuaian Patokan BBM)
            $table->decimal('baseline_override', 5, 2)->nullable()
                ->comment('Patokan BBM yang disesuaikan admin untuk trip ini (km/L)');
            $table->text('baseline_override_reason')->nullable()
                ->comment('Alasan penyesuaian patokan BBM (kondisi khusus)');
            $table->unsignedBigInteger('baseline_overridden_by')->nullable()
                ->comment('User ID yang melakukan penyesuaian patokan');
            $table->timestamp('baseline_overridden_at')->nullable()
                ->comment('Waktu penyesuaian patokan');

            // ==================== PREMATURE REFUEL DETECTION ====================
            $table->decimal('expected_range_km', 8, 2)->nullable()
                ->comment('Jarak tempuh optimal dari volume BBM (fuel_volume * expected_efficiency)');
            $table->decimal('range_usage_percent', 6, 2)->nullable()
                ->comment('Persentase pemakaian dari range optimal (%)');
            $table->boolean('is_premature_refuel')->default(false)
                ->comment('Apakah refuel sebelum waktunya? (range_usage < 60%)');

            // ==================== ALERT & SYSTEM NOTES ====================
            $table->enum('alert_level', ['normal', 'warning', 'critical'])->default('normal')
                ->comment('Level peringatan: normal/warning/critical');
            $table->text('system_notes')->nullable()
                ->comment('Catatan otomatis dari sistem (auto-generated)');

            // ==================== VALIDATION DATA ====================
            $table->boolean('is_long_trip')->default(false)
                ->comment('True = Perjalanan Dinas Jarak Jauh (≥300 KM)');
            $table->text('long_trip_notes')->nullable()
                ->comment('Keterangan tujuan perjalanan (Jakarta, Dieng, dll)');

            // ==================== VALIDATOR INFO ====================
            $table->unsignedBigInteger('validated_by')->nullable()
                ->comment('User ID yang melakukan validasi');
            $table->timestamp('validated_at')->nullable()
                ->comment('Waktu validasi');

            // ==================== ADDITIONAL METADATA ====================
            $table->integer('days_between_refuel')->nullable()
                ->comment('Jumlah hari antara 2 transaksi');

            $table->timestamps();

            // ==================== FOREIGN KEYS ====================
            $table->foreign('transaction_proof_previous_id')
                  ->references('id')
                  ->on('transaction_proofs')
                  ->onDelete('cascade')
                  ->name('fk_trip_val_proof_prev');

            $table->foreign('transaction_proof_latest_id')
                  ->references('id')
                  ->on('transaction_proofs')
                  ->onDelete('cascade')
                  ->name('fk_trip_val_proof_latest');

            $table->foreign('validated_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null')
                  ->name('fk_trip_val_validator');

            // ✅ NEW: Foreign key untuk baseline override
            $table->foreign('baseline_overridden_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null')
                  ->name('fk_trip_val_baseline_overrider');

            // ==================== INDEXES UNTUK PERFORMANCE ====================
            $table->index('transaction_proof_previous_id', 'idx_trip_val_prev');
            $table->index('transaction_proof_latest_id', 'idx_trip_val_latest');
            $table->index('diff_odo', 'idx_trip_val_diff_odo');
            $table->index('is_long_trip', 'idx_trip_val_is_long');
            $table->index('validated_at', 'idx_trip_val_validated_at');
            $table->index('alert_level', 'idx_trip_val_alert_level');
            $table->index('is_premature_refuel', 'idx_trip_val_premature');
            $table->index('efficiency_deviation_percent', 'idx_trip_val_efficiency_dev');

            // ==================== UNIQUE CONSTRAINT ====================
            $table->unique(
                ['transaction_proof_previous_id', 'transaction_proof_latest_id'],
                'uk_trip_val_proof_pair'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_validations');
    }
};
