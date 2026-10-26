<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces users.default_location (enum PMU/Patuha) with a proper FK to
     * sites. This is the "Default Site" from Phase 1 bootstrap: every user's
     * fallback lunch location when there is no manual override and no
     * historical pattern yet.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('default_site_id')->nullable()->after('default_location')
                ->constrained('sites')->nullOnDelete();
        });

        DB::table('users')->where('default_location', 'PMU')->update(['default_site_id' => 1]);
        DB::table('users')->where('default_location', 'Patuha')->update(['default_site_id' => 2]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('default_location');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('default_location', ['PMU', 'Patuha'])->nullable()->after('jabatan');
        });

        $sites = DB::table('sites')->pluck('code', 'id');
        foreach ($sites as $id => $code) {
            DB::table('users')->where('default_site_id', $id)->update(['default_location' => $code]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_site_id');
        });
    }
};
