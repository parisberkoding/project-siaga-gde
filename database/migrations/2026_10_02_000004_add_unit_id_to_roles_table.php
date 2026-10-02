<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item 4 — Segmentasi role per unit.
 *
 * Kebutuhan: "superadmin unit pasti akan berbeda dengan superadmin pusat".
 *
 * Pendekatan yang dipilih SUDAH unit_id NULLABLE, bukan memaksa satu role per
 * unit. Alasannya penting:
 *
 *   unit_id = NULL  -> role GLOBAL, berlaku di semua unit (8 role bawaan).
 *                      Diperlukan supaya role yang ada sekarang tetap sah
 *                      setelah user di-assign ke unit — kalau role global
 *                      dilarang untuk user ber-unit, seluruh user eksisting
 *                      akan kehilangan hak aksesnya sekaligus.
 *
 *   unit_id = <id>  -> role KHUSUS unit itu, hanya boleh diberikan ke user
 *                      di unit tersebut.
 *
 * Dengan begitu Pusat DAPAT membuat role superadmin khusus sebuah unit dengan
 * kumpulan permission yang berbeda, tanpa memaksa duplikasi 8 role x N unit
 * yang akan meledak secara kombinatorial.
 *
 * Kontribusi utama untuk pemisahan Pusat vs unit tetap dua hal yang sudah ada:
 *   - units.is_central + EnsureCentralUnit (hak konfigurasi hanya Pusat)
 *   - gate modul per unit (modul mana yang berlaku di unit itu)
 * Role ber-unit melengkapinya sebagai lapisan ketiga yang opsional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('code')
                ->constrained('units')->nullOnDelete();

            $table->index('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};
