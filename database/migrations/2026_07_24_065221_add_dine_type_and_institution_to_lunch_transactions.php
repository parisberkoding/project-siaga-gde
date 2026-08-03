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
        Schema::table('lunch_transactions', function (Blueprint $table) {
            $table->string('dine_type', 20)->nullable()->after('tomorrow_site_id');
            $table->string('institution', 200)->nullable()->after('dine_type');
        });
    }

    public function down(): void
    {
        Schema::table('lunch_transactions', function (Blueprint $table) {
            $table->dropColumn(['dine_type', 'institution']);
        });
    }
};
