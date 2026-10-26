<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Standarisasi slug (route key URL):
     * Semua baris yang slug-nya kosong di-backfill dari nomor surat
     * (slash diganti strip) agar tautan detail/validasi tidak pecah.
     */
    public function up(): void
    {
        $this->backfillSuratSpd();
        $this->backfillLpj();
    }

    private function backfillSuratSpd(): void
    {
        $rows = DB::table('surat_spds')
            ->where(fn ($q) => $q->whereNull('slug')->orWhere('slug', ''))
            ->get();

        foreach ($rows as $row) {
            $slug = str_replace('/', '-', (string) $row->nomor_surat);

            // Hindari konflik slug duplikat bila nomor surat kembar.
            $candidate = $slug;
            $suffix = 1;
            while (DB::table('surat_spds')->where('slug', $candidate)->where('id', '!=', $row->id)->exists()) {
                $candidate = $slug.'-'.(++$suffix);
            }

            DB::table('surat_spds')->where('id', $row->id)->update(['slug' => $candidate]);
        }
    }

    private function backfillLpj(): void
    {
        $rows = DB::table('lpj_penggantians')
            ->where(fn ($q) => $q->whereNull('slug')->orWhere('slug', ''))
            ->get();

        foreach ($rows as $row) {
            $slug = str_replace('/', '-', (string) $row->lpj_number);

            $candidate = $slug;
            $suffix = 1;
            while (DB::table('lpj_penggantians')->where('slug', $candidate)->where('id', '!=', $row->id)->exists()) {
                $candidate = $slug.'-'.(++$suffix);
            }

            if ($candidate !== '') {
                DB::table('lpj_penggantians')->where('id', $row->id)->update(['slug' => $candidate]);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive; slug kosong/terisi tetap dibiarkan.
    }
};