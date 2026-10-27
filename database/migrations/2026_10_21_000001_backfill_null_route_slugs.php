<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill route-key slug yang kosong.
     *
     * Insiden: baris `surat_spds` hasil seeder lama tidak punya slug karena
     * seeder menyimpan lewat DB::table / withoutEvents (event `creating` yang
     * biasanya mengisi slug tidak berjalan). Akibatnya setiap
     * route('surat.superadmin.show', $surat) melempar:
     *
     *     Missing required parameter for [Route: surat.superadmin.show]
     *
     * Migrasi ini memperbaiki data lama. Untuk data baru, seeder sudah diisi
     * slug eksplisit dan model memakai fallback ID (HasSafeSlugRouteKey).
     */
    public function up(): void
    {
        $this->backfill(
            'surat_spds',
            'slug',
            fn (object $row) => str_replace('/', '-', (string) $row->nomor_surat)
        );

        $this->backfill(
            'surat_spd_extensions',
            'slug',
            fn (object $row) => str_replace(
                '/',
                '-',
                (string) ($row->nomor_surat_extension ?: $row->extension_number ?: 'EXT-'.$row->id)
            )
        );

        $this->backfill(
            'lpj_penggantians',
            'slug',
            fn (object $row) => str_replace('/', '-', (string) $row->lpj_number)
        );

        $this->backfill(
            'spd_daily_reports',
            'slug',
            fn () => 'LHP-'.strtoupper(bin2hex(random_bytes(5)))
        );

        $this->backfill(
            'struk_items',
            'slug',
            fn () => 'ITM-'.strtoupper(bin2hex(random_bytes(5)))
        );
    }

    /**
     * Isi slug untuk semua baris yang masih kosong, dengan penjaga duplikat.
     *
     * @param  callable(object): string  $valueFor
     */
    private function backfill(string $table, string $column, callable $valueFor): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)
            || ! DB::getSchemaBuilder()->hasColumn($table, $column)) {
            return;
        }

        $rows = DB::table($table)
            ->where(fn ($query) => $query->whereNull($column)->orWhere($column, ''))
            ->get();

        foreach ($rows as $row) {
            $base = trim($valueFor($row));

            if ($base === '') {
                $base = 'ROW-'.$row->id;
            }

            $candidate = $base;
            $suffix = 1;

            while (DB::table($table)
                ->where($column, $candidate)
                ->where('id', '!=', $row->id)
                ->exists()) {
                $candidate = $base.'-'.(++$suffix);
            }

            DB::table($table)->where('id', $row->id)->update([$column => $candidate]);
        }
    }

    public function down(): void
    {
        // Non-destruktif: slug yang sudah diisi tidak dikosongkan kembali.
    }
};
