<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BUG-SPD-003 — `surat_spds.nomor_urut` sebelumnya varchar sehingga
 * pengurutan `orderBy('nomor_urut', 'desc')` bersifat leksikografis
 * ("9" > "10"), dan nomor surat ke-11 selalu menjadi 10 (duplikat).
 *
 * Migrasi ini mengubah kolom menjadi unsigned integer (nullable) dan
 * mem-backfill nilai lama secara aman:
 *   - string kosong / non-numerik / negatif / desimal -> NULL
 *   - string numerik bulat (termasuk "007")          -> 7
 *
 * Portable MySQL & SQLite:
 *   - MySQL memakai ALTER TABLE ... MODIFY (change()).
 *   - SQLite memakai fallback add-column / copy / drop / rename karena
 *     ALTER COLUMN tidak didukung native oleh SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->backfillNomorUrut();

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('surat_spds', function (Blueprint $table) {
                $table->unsignedInteger('nomor_urut_int')->nullable();
            });

            DB::table('surat_spds')
                ->whereNotNull('nomor_urut')
                ->update(['nomor_urut_int' => DB::raw('CAST(nomor_urut AS INTEGER)')]);

            Schema::table('surat_spds', function (Blueprint $table) {
                $table->dropColumn('nomor_urut');
            });

            Schema::table('surat_spds', function (Blueprint $table) {
                $table->renameColumn('nomor_urut_int', 'nomor_urut');
            });

            return;
        }

        Schema::table('surat_spds', function (Blueprint $table) {
            $table->unsignedInteger('nomor_urut')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('surat_spds', function (Blueprint $table) {
                $table->string('nomor_urut_str')->nullable();
            });

            DB::table('surat_spds')
                ->whereNotNull('nomor_urut')
                ->update(['nomor_urut_str' => DB::raw('CAST(nomor_urut AS TEXT)')]);

            Schema::table('surat_spds', function (Blueprint $table) {
                $table->dropColumn('nomor_urut');
            });

            Schema::table('surat_spds', function (Blueprint $table) {
                $table->renameColumn('nomor_urut_str', 'nomor_urut');
            });

            return;
        }

        Schema::table('surat_spds', function (Blueprint $table) {
            $table->string('nomor_urut')->nullable()->change();
        });
    }

    /**
     * Normalisasi nilai lama sebelum tipe kolom diubah.
     *
     * Dilakukan lewat Eloquent/Query Builder (bukan SQL spesifik driver) agar
     * hasilnya sama di MySQL maupun SQLite.
     */
    private function backfillNomorUrut(): void
    {
        DB::table('surat_spds')
            ->select(['id', 'nomor_urut'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $raw = $row->nomor_urut;

                    $value = null;

                    if ($raw !== null) {
                        $trimmed = trim((string) $raw);

                        if ($trimmed !== '' && ctype_digit($trimmed)) {
                            $value = (int) $trimmed;
                        }
                    }

                    DB::table('surat_spds')
                        ->where('id', $row->id)
                        ->update(['nomor_urut' => $value]);
                }
            });
    }
};
