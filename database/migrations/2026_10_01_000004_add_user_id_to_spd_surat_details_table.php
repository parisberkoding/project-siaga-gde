<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simpan ID user (akun) untuk tiap pemakai/penumpang SPD.
     *
     * Tabel penumpang sudah ada (spd_surat_details: surat_spd_id | nama_lengkap | urutan),
     * sehingga cukup menambah kolom user_id di sini alih-alih membuat tabel baru
     * yang menduplikasi data nama.
     *
     * user_id NULLABLE: penumpang boleh tidak terdaftar di sistem (tanpa akun),
     * sehingga hanya nama yang tersimpan.
     */
    public function up(): void
    {
        Schema::table('spd_surat_details', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('nama_lengkap')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('spd_surat_details', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
