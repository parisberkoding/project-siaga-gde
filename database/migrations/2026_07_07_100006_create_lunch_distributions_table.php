<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lunch Distribution = employee prediction totals + guest reservation
     * totals, per site per day. Stored as a locked snapshot rather than
     * computed on the fly: once a count is sent to the catering vendor, it
     * must not silently change if later predictions/reservations change.
     * Fully rebuildable from lunch_trackings + lunch_guest_reservations, so
     * this is a materialized cache, not a new source of truth.
     */
    public function up(): void
    {
        Schema::create('lunch_distributions', function (Blueprint $table) {
            $table->id();

            $table->date('distribution_date');
            $table->foreignId('site_id')->constrained('sites')->restrictOnDelete();

            $table->unsignedInteger('employee_count')->default(0);
            $table->unsignedInteger('guest_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);

            $table->foreignId('forecast_batch_id')->nullable()
                ->constrained('lunch_forecast_batches')->nullOnDelete();

            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['distribution_date', 'site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lunch_distributions');
    }
};
