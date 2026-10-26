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
        Schema::table('surat_spds', function (Blueprint $table) {
            $table->enum('type', ['biasa', 'mendadak'])->default('biasa')->after('catatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_spds', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
