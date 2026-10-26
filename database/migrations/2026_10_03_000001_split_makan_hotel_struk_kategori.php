<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pisahkan kategori lama "Hotel + Makan" menjadi dua kategori mandiri:
 * "Makan" dan "Hotel".
 *
 * Alasan: dokumen LPJ SPD yang baru (Lembar Persetujuan Penggantian
 * Pertanggungjawaban & Lembar Ringkasan Penggunaan Biaya Perjalanan Dinas)
 * memiliki kolom terpisah untuk "Biaya Makan" dan "Biaya Hotel", sehingga satu
 * kategori gabungan tidak lagi bisa memetakan nominal ke kolom yang benar.
 *
 * Catatan: enum di DB MySQL saat ini hanya berisi
 * ['BBM','Tol','Parkir','Lainnya','Tambahan'] — nilai 'Hotel + Makan' sempat
 * dipakai aplikasi tetapi tidak pernah ada di enum. Migrasi ini menambahkannya
 * sementara agar baris lama (bila ada) bisa direklasifikasi tanpa error,
 * lalu enum final dibentuk tanpa nilai gabungan tersebut.
 *
 * Driver-aware: MySQL memakai ALTER ... MODIFY (butuh nilai sementara),
 * sedangkan SQLite (dipakai test suite) tidak punya enum asli sehingga cukup
 * memakai Schema::change().
 */
return new class extends Migration
{
    private const ENUM_LAMA = ['BBM', 'Tol', 'Parkir', 'Hotel + Makan', 'Makan', 'Hotel', 'Lainnya', 'Tambahan'];

    private const ENUM_BARU = ['BBM', 'Tol', 'Parkir', 'Makan', 'Hotel', 'Lainnya', 'Tambahan'];

    private const ENUM_DETAIL_BARU = ['BBM', 'Tol', 'Parkir', 'Makan', 'Hotel', 'Lain-lain'];

    public function up(): void
    {
        $this->ubahEnumStruk(self::ENUM_LAMA);

        // ── Reklasifikasi baris lama berbasis kata kunci ────────────────────
        DB::table('struk_items')
            ->where('struk_kategori', 'Hotel + Makan')
            ->orderBy('id')
            ->each(function ($row) {
                $teks = strtolower(trim(
                    ($row->ocr_text ?? '') . ' ' . ($row->keterangan ?? '') . ' ' . ($row->metadata_json ?? '')
                ));

                $kataHotel = ['hotel', 'penginapan', 'inn', 'room', 'kamar', 'resort', 'villa', 'losmen', 'homestay'];
                $baru = 'Makan';

                foreach ($kataHotel as $kata) {
                    if ($teks !== '' && str_contains($teks, $kata)) {
                        $baru = 'Hotel';
                        break;
                    }
                }

                DB::table('struk_items')->where('id', $row->id)->update(['struk_kategori' => $baru]);
            });

        $this->ubahEnumStruk(self::ENUM_BARU);

        // ── Samakan enum detail LPJ (nilai tipe diturunkan dari kategori) ───
        $this->ubahEnumDetail(self::ENUM_DETAIL_BARU);
    }

    public function down(): void
    {
        $this->ubahEnumStruk(self::ENUM_BARU);

        DB::table('struk_items')
            ->whereIn('struk_kategori', ['Makan', 'Hotel'])
            ->update(['struk_kategori' => 'Lainnya']);

        $this->ubahEnumStruk(['BBM', 'Tol', 'Parkir', 'Lainnya', 'Tambahan']);

        DB::table('lpj_penggantian_details')
            ->whereIn('tipe_penggunaan', ['Makan', 'Hotel'])
            ->update(['tipe_penggunaan' => 'Lain-lain']);

        $this->ubahEnumDetail(['BBM', 'Tol', 'Parkir', 'Lain-lain']);
    }

    private function ubahEnumStruk(array $nilai): void
    {
        if (DB::getDriverName() === 'mysql') {
            $list = implode("','", $nilai);
            DB::statement("ALTER TABLE struk_items MODIFY struk_kategori ENUM('{$list}') NOT NULL DEFAULT 'Lainnya'");

            return;
        }

        Schema::table('struk_items', function (Blueprint $table) use ($nilai) {
            $table->enum('struk_kategori', $nilai)->default('Lainnya')->change();
        });
    }

    private function ubahEnumDetail(array $nilai): void
    {
        if (DB::getDriverName() === 'mysql') {
            $list = implode("','", $nilai);
            DB::statement("ALTER TABLE lpj_penggantian_details MODIFY tipe_penggunaan ENUM('{$list}') NOT NULL");

            return;
        }

        Schema::table('lpj_penggantian_details', function (Blueprint $table) use ($nilai) {
            $table->enum('tipe_penggunaan', $nilai)->change();
        });
    }
};
