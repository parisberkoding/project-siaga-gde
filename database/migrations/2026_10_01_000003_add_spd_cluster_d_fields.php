<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cluster D — SPD, LPJ & Signature.
     *
     * 1. Surat SPD: kode GL (SAP), uang diberikan, dan jenis SPD (dalam/luar kota).
     * 2. LPJ: tambah tahap validasi Management (supervisor tertinggi).
     * 3. Tabel penambahan dana pada surat SPD yang sudah disetujui.
     */
    public function up(): void
    {
        Schema::table('surat_spds', function (Blueprint $table) {
            $table->string('kode_gl')->nullable()->after('type');
            $table->decimal('uang_diberikan', 15, 2)->nullable()->after('kode_gl');
            $table->string('jenis_spd')->nullable()->after('uang_diberikan');
            $table->string('jenis_spd_lainnya')->nullable()->after('jenis_spd');
        });

        Schema::table('lpj_penggantians', function (Blueprint $table) {
            $table->foreignId('approved_by_management')->nullable()->after('approved_divisi_ga_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_management_at')->nullable()->after('approved_by_management');
        });

        Schema::create('surat_spd_fund_additions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_spd_id')->constrained('surat_spds')->cascadeOnDelete();
            $table->decimal('nominal_tambahan', 15, 2);
            $table->text('alasan')->nullable();
            $table->string('bukti_transfer_path')->nullable();
            $table->string('bank_reference')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_spd_fund_additions');

        Schema::table('lpj_penggantians', function (Blueprint $table) {
            $table->dropForeign(['approved_by_management']);
            $table->dropColumn(['approved_by_management', 'approved_management_at']);
        });

        Schema::table('surat_spds', function (Blueprint $table) {
            $table->dropColumn(['kode_gl', 'uang_diberikan', 'jenis_spd', 'jenis_spd_lainnya']);
        });
    }
};
