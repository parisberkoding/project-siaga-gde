<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Turns lunch_trackings from a vehicle-trip-only log into the full
     * Lunch Prediction history described by the business flow: predicted
     * site, actual site, who/what produced the prediction, its confidence,
     * and whether it turned out correct. Vehicle tracking becomes just one
     * of several prediction_source values instead of the only source.
     *
     * - trip_id (required, cascade) -> source_trip_id (nullable, nullOnDelete):
     *   not every lunch record originates from a trip anymore (manual/
     *   historical/default predictions don't), and losing a trip record
     *   should not delete lunch history that is used for learning.
     * - lunch_location_type/is_catering_counted (2-value enum + flag) ->
     *   predicted_site_id/actual_site_id (FK to sites): normalized, and
     *   distinguishes "what we predicted" from "what actually happened".
     * - unique(trip_id, user_id) -> unique(user_id, lunch_date): lunch
     *   happens once a day per employee, not once per trip: a user with
     *   two trips in one day must still resolve to a single lunch record.
     */
    public function up(): void
    {
        Schema::table('lunch_trackings', function (Blueprint $table) {
            $table->foreignId('predicted_site_id')->nullable()->after('lunch_date')
                ->constrained('sites')->nullOnDelete();

            $table->foreignId('actual_site_id')->nullable()->after('predicted_site_id')
                ->constrained('sites')->nullOnDelete();

            $table->enum('prediction_source', ['default', 'manual', 'historical', 'vehicle', 'planning'])
                ->default('vehicle')->after('actual_site_id');

            $table->decimal('confidence', 5, 2)->nullable()->after('prediction_source');

            $table->boolean('is_prediction_correct')->nullable()->after('confidence');

            $table->foreignId('forecast_batch_id')->nullable()->after('is_prediction_correct')
                ->constrained('lunch_forecast_batches')->nullOnDelete();

            $table->foreignId('source_trip_id')->nullable()->after('forecast_batch_id')
                ->constrained('trips')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->after('source_trip_id')
                ->constrained('users')->nullOnDelete();

            $table->foreignId('updated_by')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();
        });

        // Every existing row is a raw vehicle-tracking observation, not a
        // prediction: fill actual_site_id, leave predicted_site_id/
        // is_prediction_correct null (no forecast ever existed for them).
        DB::table('lunch_trackings')->where('lunch_location_type', 'site_pmu')
            ->update(['actual_site_id' => 1, 'prediction_source' => 'vehicle']);
        DB::table('lunch_trackings')->where('lunch_location_type', 'site_patuha')
            ->update(['actual_site_id' => 2, 'prediction_source' => 'vehicle']);

        DB::table('lunch_trackings')->update(['source_trip_id' => DB::raw('trip_id')]);

        // A user could have >1 trip (and therefore >1 row) on the same day
        // under the old unique(trip_id, user_id). Keep only the latest row
        // per user/day before the new unique(user_id, lunch_date) is added.
        DB::statement('
            DELETE FROM lunch_trackings
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT MAX(id) AS id FROM lunch_trackings GROUP BY user_id, lunch_date
                ) AS keep_rows
            )
        ');

        Schema::table('lunch_trackings', function (Blueprint $table) {
            $table->dropForeign(['trip_id']);
            $table->dropUnique(['trip_id', 'user_id']);
            $table->dropColumn(['trip_id', 'lunch_location_type', 'is_catering_counted']);

            $table->unique(['user_id', 'lunch_date']);
        });
    }

    public function down(): void
    {
        Schema::table('lunch_trackings', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'lunch_date']);

            $table->unsignedBigInteger('trip_id')->nullable()->after('id');
            $table->enum('lunch_location_type', ['site_pmu', 'site_patuha', 'external', 'none'])->nullable();
            $table->boolean('is_catering_counted')->default(false);
        });

        DB::table('lunch_trackings')->update(['trip_id' => DB::raw('source_trip_id')]);
        DB::table('lunch_trackings')->where('actual_site_id', 1)->update(['lunch_location_type' => 'site_pmu']);
        DB::table('lunch_trackings')->where('actual_site_id', 2)->update(['lunch_location_type' => 'site_patuha']);

        Schema::table('lunch_trackings', function (Blueprint $table) {
            $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade');
            $table->unique(['trip_id', 'user_id']);

            $table->dropConstrainedForeignId('predicted_site_id');
            $table->dropConstrainedForeignId('actual_site_id');
            $table->dropColumn('prediction_source');
            $table->dropColumn('confidence');
            $table->dropColumn('is_prediction_correct');
            $table->dropConstrainedForeignId('forecast_batch_id');
            $table->dropConstrainedForeignId('source_trip_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
