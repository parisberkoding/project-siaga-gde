<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('trip_code')->unique()->nullable()->after('id');
        });

        // Backfill existing rows
        $trips = DB::table('trips')->orderBy('id')->get();
        foreach ($trips as $trip) {
            $date = date('Ymd', strtotime($trip->created_at ?? now()));
            DB::table('trips')->where('id', $trip->id)->update([
                'trip_code' => 'TRIP-' . $date . '-' . str_pad($trip->id, 3, '0', STR_PAD_LEFT),
            ]);
        }

        // Make non-nullable after backfill
        Schema::table('trips', function (Blueprint $table) {
            $table->string('trip_code')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('trip_code');
        });
    }
};
