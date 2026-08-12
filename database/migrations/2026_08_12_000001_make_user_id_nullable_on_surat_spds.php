<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_spds', function (Blueprint $table) {
            // Lepas FK lama (restrict) agar bisa diubah nullable
            $table->dropForeign(['user_id']);
        });

        Schema::table('surat_spds', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Link opsional ke Master Pekerja (employee_defaults) — pengemudi
            // bisa berasal dari pekerja tanpa akun login.
            $table->unsignedBigInteger('employee_default_id')->nullable()->after('user_id');
            $table->foreign('employee_default_id')->references('id')->on('employee_defaults')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('surat_spds', function (Blueprint $table) {
            $table->dropForeign(['employee_default_id']);
            $table->dropColumn('employee_default_id');

            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });
    }
};
