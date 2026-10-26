<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akses Fitur per unit x CRUD — checklist "Akses Fitur".
 *
 * Ini lapisan ketiga yang LEBIH HALUS dari gate modul per unit:
 *
 *   unit_modules          -> "modul ini ADA untuk unit ini?"   (kasar, biner)
 *   unit_feature_access   -> "unit ini boleh CREATE/READ/UPDATE/DELETE fitur X?"
 *                            (halus, per fitur, per aksi)
 *
 * Keduanya sengaja terpisah. unit_modules menjawab ketersediaan modul secara
 * menyeluruh; tabel ini menjawab hak akses per aksi CRUD pada tiap fitur.
 *
 * Catatan status implementasi: tabel ini menyimpan matrix konfigurasi dan
 * menggerakkan UI "Akses Fitur". PENEGAKAN runtime (memakai matrix ini untuk
 * menyaring akses user saat request) adalah langkah lanjutan yang belum
 * diaktifkan — lihat UnitFeatureAccess::isWiredIntoAuth().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_feature_access', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();

            // Default: boleh melihat (read) supaya tidak ada fitur yang tiba-tiba
            // hilang; aksi tulis dimulai dari false (opt-in).
            $table->boolean('can_view')->default(true);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);

            $table->timestamps();

            $table->unique(['unit_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_feature_access');
    }
};
