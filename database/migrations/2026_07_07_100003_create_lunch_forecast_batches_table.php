<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Groups every H-1 forecast run so a single generation event can be
     * audited: which algorithm produced it, how much history it was based
     * on, and whether it was later finalized. lunch_trackings and
     * lunch_distributions both optionally point back to a batch.
     */
    public function up(): void
    {
        Schema::create('lunch_forecast_batches', function (Blueprint $table) {
            $table->id();

            // Lunch date this batch is forecasting (usually generated H-1).
            $table->date('forecast_date');

            $table->string('generated_by_type', 30)->default('system'); // system|manual
            $table->foreignId('generated_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();

            // Bump this when the prediction algorithm changes (default rule,
            // historical-pattern rule, future ML model, ...).
            $table->string('algorithm_version', 30)->default('v1');

            // How many historical records fed the pattern-learning step.
            $table->unsignedInteger('history_sample_size')->default(0);

            $table->enum('status', ['draft', 'finalized'])->default('draft');
            $table->timestamp('finalized_at')->nullable();

            $table->timestamps();

            $table->index('forecast_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lunch_forecast_batches');
    }
};
