<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

/**
 * Lampiran Pemberian Dana Perjalanan Dinas.
 *
 * Tabel `surat_spd_budgets` sudah menyimpan rencana/pemberian dana per tanggal
 * untuk sebuah surat SPD (dipakai juga sebagai basis perhitungan over-budget).
 * Agar bisa menjadi "Lembar Lampiran Pemberian Dana Perjalanan Dinas", tabel ini
 * dilengkapi kolom pencairan:
 *
 *   - bentuk_pencairan : Tunai / Transfer
 *   - bank_reference   : nomor referensi perbankan (untuk pencairan transfer)
 *   - rek_tujuan       : rekening tujuan transfer
 *   - urutan           : urutan tampil baris pada dokumen cetak
 *
 * Kolom `catatan` yang sudah ada tetap dipakai sebagai keterangan tambahan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_spd_budgets', function (Blueprint $table) {
            $table->enum('bentuk_pencairan', ['Tunai', 'Transfer'])
                ->nullable()
                ->after('nominal')
                ->comment('Bentuk pencairan dana: tunai atau transfer bank');
            $table->string('bank_reference')->nullable()->after('bentuk_pencairan')
                ->comment('Nomor referensi perbankan (wajib untuk transfer)');
            $table->string('rek_tujuan')->nullable()->after('bank_reference')
                ->comment('Rekening tujuan pencairan transfer');
            $table->integer('urutan')->default(1)->after('rek_tujuan');

            $table->index(['surat_spd_id', 'urutan'], 'spd_budgets_surat_urutan_index');
        });
    }

    public function down(): void
    {
        Schema::table('surat_spd_budgets', function (Blueprint $table) {
            $table->dropIndex('spd_budgets_surat_urutan_index');
            $table->dropColumn(['bentuk_pencairan', 'bank_reference', 'rek_tujuan', 'urutan']);
        });
    }
};
