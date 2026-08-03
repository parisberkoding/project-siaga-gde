<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_schedules', function (Blueprint $table) {
            $table->string('schedule_code')->unique()->nullable()->after('id');
        });

        // Backfill existing rows
        $schedules = DB::table('driver_schedules')->orderBy('id')->get();
        foreach ($schedules as $schedule) {
            $date = date('Ymd', strtotime($schedule->created_at ?? now()));
            DB::table('driver_schedules')->where('id', $schedule->id)->update([
                'schedule_code' => 'SCH-' . $date . '-' . str_pad($schedule->id, 3, '0', STR_PAD_LEFT),
            ]);
        }

        // Make non-nullable after backfill
        Schema::table('driver_schedules', function (Blueprint $table) {
            $table->string('schedule_code')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('driver_schedules', function (Blueprint $table) {
            $table->dropColumn('schedule_code');
        });
    }
};
