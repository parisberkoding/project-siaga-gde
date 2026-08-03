<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_booking_requests', function (Blueprint $table) {
            $table->string('booking_code')->unique()->nullable()->after('id');
        });

        // Backfill existing rows
        $bookings = DB::table('trip_booking_requests')->orderBy('id')->get();
        foreach ($bookings as $booking) {
            $date = date('Ymd', strtotime($booking->created_at ?? now()));
            DB::table('trip_booking_requests')->where('id', $booking->id)->update([
                'booking_code' => 'BKG-' . $date . '-' . str_pad($booking->id, 3, '0', STR_PAD_LEFT),
            ]);
        }

        // Make non-nullable after backfill
        Schema::table('trip_booking_requests', function (Blueprint $table) {
            $table->string('booking_code')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('trip_booking_requests', function (Blueprint $table) {
            $table->dropColumn('booking_code');
        });
    }
};
