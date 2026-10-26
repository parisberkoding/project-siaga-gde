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
        Schema::create('vehicle_trip_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('consumerial_tools_id')->constrained('vehicle_and_tools_consumers')->onDelete('cascade');

            // Data pengisian awal
            $table->foreignId('transaction_proof_start_id')->constrained('transaction_proofs')->onDelete('cascade');
            $table->integer('odo_start'); // Odometer awal
            $table->decimal('fuel_volume_start', 8, 2); // Volume BBM awal
            $table->dateTime('filling_date_start'); // Tanggal pengisian awal

            // Data pengisian akhir
            $table->foreignId('transaction_proof_end_id')->constrained('transaction_proofs')->onDelete('cascade');
            $table->integer('odo_end'); // Odometer akhir
            $table->decimal('fuel_volume_end', 8, 2); // Volume BBM akhir
            $table->dateTime('filling_date_end'); // Tanggal pengisian akhir

            // Analisis
            $table->integer('km_difference'); // Selisih KM (odo_end - odo_start)
            $table->decimal('fuel_consumed', 8, 2); // Total BBM yang dikonsumsi (volume_start)
            $table->decimal('fuel_efficiency', 8, 2)->nullable(); // KM per liter
            $table->integer('days_difference'); // Selisih hari

            // Status & Validasi
            $table->enum('trip_category', ['normal', 'long_distance', 'suspicious'])->default('normal');
            // normal: < 400km
            // long_distance: >= 400km
            // suspicious: anomali konsumsi BBM

            $table->boolean('is_validated')->default(false);
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('validated_at')->nullable();
            $table->enum('validation_status', ['pending', 'confirmed_long_distance', 'confirmed_normal', 'flagged'])->default('pending');

            // Catatan
            $table->text('system_notes')->nullable(); // Catatan otomatis dari sistem
            $table->text('admin_notes')->nullable(); // Catatan dari admin/superadmin

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('requester_id');
            $table->index('consumerial_tools_id');
            $table->index('trip_category');
            $table->index('is_validated');
            $table->index('filling_date_start');
            $table->index('filling_date_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_trip_analyses');
    }
};
