<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah field pendukung cetak LPJ SPD gabungan (Surat + Ringkasan + Foto).
     *
     * - odo_before        : odometer kendaraan sebelum SPD (KM)
     * - odo_after         : odometer kendaraan setelah SPD (KM)
     * - kebutuhan_bbm_liter: estimasi kebutuhan BBM perjalanan (liter)
     *
     * Field ini bersifat opsional (tidak wajib diisi) karena alur SPD baru
     * memisahkan budgeting; dokumentasi tempuh hanya pelengkap LPJ.
     */
    public function up(): void
    {
        Schema::table('lpj_penggantians', function (Blueprint $table) {
            $table->decimal('odo_before', 15, 2)->nullable()->after('nomor_polisi');
            $table->decimal('odo_after', 15, 2)->nullable()->after('odo_before');
            $table->decimal('kebutuhan_bbm_liter', 15, 2)->nullable()->after('odo_after');
        });
    }

    public function down(): void
    {
        Schema::table('lpj_penggantians', function (Blueprint $table) {
            $table->dropColumn(['odo_before', 'odo_after', 'kebutuhan_bbm_liter']);
        });
    }
};
