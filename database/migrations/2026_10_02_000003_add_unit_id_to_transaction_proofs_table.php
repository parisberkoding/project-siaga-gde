<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Item 1 — Penanda unit pada BBM Operasional.
 *
 * Sebelum migrasi ini, rantai BBM tidak konsisten dalam hal penanda unit:
 *   permohonans        -> punya unit_id
 *   approved_requests  -> punya unit_id
 *   surat_spds         -> punya unit_id
 *   transaction_proofs -> TIDAK PUNYA (hanya bisa diturunkan lewat relasi permohonan)
 *
 * Akibatnya setiap pelaporan bukti pembelian BBM harus menempuh whereHas ke
 * permohonan hanya untuk menyaring unit — mahal dan tidak bisa diindeks.
 * Kolom ini membuat unit dapat disaring langsung, sekaligus menyimpan unit
 * pada saat bukti dibuat (sehingga bukti tetap menempel ke unit asalnya
 * walau permohonannya kelak berpindah unit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_proofs', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('requester_id')
                ->constrained('units')->nullOnDelete();
        });

        $this->backfillFromPermohonan();
    }

    /**
     * Isi unit_id dari permohonan induk.
     *
     * WAJIB memakai chunkById(), BUKAN chunk(). Query ini menyaring
     * `whereNull('unit_id')` sambil MENGISI `unit_id` — dengan chunk() yang
     * memakai OFFSET, hasil query menyusut setiap batch sehingga ratusan baris
     * terlewat tanpa error. chunkById() memaginasi lewat primary key sehingga
     * perubahan kolom filter tidak menggeser posisi baca.
     *
     * Juga portabel antara MySQL dan SQLite (dipakai test suite).
     */
    private function backfillFromPermohonan(): void
    {
        DB::table('transaction_proofs')
            ->select('id', 'req_id')
            ->whereNull('unit_id')
            ->chunkById(200, function ($rows) {
                $unitByReqId = DB::table('permohonans')
                    ->whereIn('id', $rows->pluck('req_id')->unique()->all())
                    ->pluck('unit_id', 'id');

                foreach ($rows as $row) {
                    $unitId = $unitByReqId[$row->req_id] ?? null;

                    if ($unitId) {
                        DB::table('transaction_proofs')
                            ->where('id', $row->id)
                            ->update(['unit_id' => $unitId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('transaction_proofs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};
