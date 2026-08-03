<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reframes catering_guests as Lunch Guest Reservation: a guest is
     * always a manual reservation (never predicted), but still feeds into
     * Lunch Distribution alongside employee predictions. Renames Indonesian
     * column names to match the rest of the redesigned schema and
     * normalizes the free-text `site` column into a sites FK. `guest_name`
     * is added because the old table only stored the institution, with no
     * separate contact/guest name field.
     */
    public function up(): void
    {
        Schema::rename('catering_guests', 'lunch_guest_reservations');

        Schema::table('lunch_guest_reservations', function (Blueprint $table) {
            $table->renameColumn('tanggal', 'reservation_date');
            $table->renameColumn('instansi', 'institution');
            $table->renameColumn('jumlah', 'guest_count');
            $table->renameColumn('catatan', 'notes');
        });

        Schema::table('lunch_guest_reservations', function (Blueprint $table) {
            $table->string('guest_name', 150)->nullable()->after('reservation_date');

            $table->foreignId('site_id')->nullable()->after('institution')
                ->constrained('sites')->restrictOnDelete();
        });

        DB::table('lunch_guest_reservations')->where('site', 'PMU')->update(['site_id' => 1]);
        DB::table('lunch_guest_reservations')->where('site', 'Patuha')->update(['site_id' => 2]);

        Schema::table('lunch_guest_reservations', function (Blueprint $table) {
            $table->dropColumn('site');
            $table->foreignId('site_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('lunch_guest_reservations', function (Blueprint $table) {
            $table->string('site', 20)->default('PMU')->after('institution');
        });

        $sites = DB::table('sites')->pluck('code', 'id');
        foreach ($sites as $id => $code) {
            DB::table('lunch_guest_reservations')->where('site_id', $id)->update(['site' => $code]);
        }

        Schema::table('lunch_guest_reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_id');
            $table->dropColumn('guest_name');
        });

        Schema::table('lunch_guest_reservations', function (Blueprint $table) {
            $table->renameColumn('reservation_date', 'tanggal');
            $table->renameColumn('institution', 'instansi');
            $table->renameColumn('guest_count', 'jumlah');
            $table->renameColumn('notes', 'catatan');
        });

        Schema::rename('lunch_guest_reservations', 'catering_guests');
    }
};
