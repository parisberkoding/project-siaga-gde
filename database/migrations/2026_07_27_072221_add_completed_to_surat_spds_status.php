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
        DB::statement("ALTER TABLE surat_spds MODIFY COLUMN status ENUM('pending','approved','rejected','extended','completed') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE surat_spds MODIFY COLUMN status ENUM('pending','approved','rejected','extended') NOT NULL DEFAULT 'pending'");
    }
};
