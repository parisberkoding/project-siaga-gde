<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cluster B — tambah province & city di units.
     * Dipakai untuk menentukan kota/alamat pada surat SPD (mis. Patuha -> Bandung).
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('province')->nullable()->after('name');
            $table->string('city')->nullable()->after('province');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['province', 'city']);
        });
    }
};
