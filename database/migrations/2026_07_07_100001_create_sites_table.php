<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "PMU" / "Patuha" used to be duplicated as a hardcoded enum/string in
     * users, pekerja_patuha, trips, trip_passengers, catering_guests and
     * config/siaga.php. A dedicated master table lets sites be added or
     * deactivated without touching schema/enum definitions, and gives the
     * prediction engine a stable foreign key to aggregate patterns per site.
     */
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed the two sites already in use. Fixed IDs (1, 2) so later
        // migrations in this batch can map old enum values deterministically.
        DB::table('sites')->insert([
            ['id' => 1, 'code' => 'PMU', 'name' => 'PMU', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'Patuha', 'name' => 'Patuha', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
