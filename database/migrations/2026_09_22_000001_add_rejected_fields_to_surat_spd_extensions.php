<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom reject pada surat_spd_extensions agar konsisten dengan
     * model SuratSpdExtension (method reject/rejector/rejection_reason).
     */
    public function up(): void
    {
        Schema::table('surat_spd_extensions', function (Blueprint $table) {
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('surat_spd_extensions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['rejected_at', 'rejection_reason']);
        });
    }
};