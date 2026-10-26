<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item 6 — Feature stage (development / production).
 *
 * Ini AXIS YANG BERBEDA dari gate modul per unit:
 *
 *   modules / unit_modules -> "modul ini ADA untuk unit ini?"     (per unit)
 *   features               -> "halaman ini sudah PRODUCTION?"     (per fitur, lintas unit)
 *
 * Keduanya sengaja tidak digabung, karena alasannya berbeda dan pesan ke user
 * juga harus berbeda:
 *   - tertahan karena unitnya tidak berhak  -> "modul tidak tersedia untuk unit Anda"
 *   - tertahan karena masih development     -> "halaman ini sedang dikembangkan"
 *
 * Kalau digabung, saat ada keluhan kita tidak bisa membedakan salah
 * konfigurasi unit atau fitur yang memang belum rilis.
 *
 * Semantik:
 *   stage = 'development' -> hanya pemegang permission features.manage
 *                            (role `developers`) yang bisa melihat.
 *   stage = 'production'  -> tunduk aturan normal (RBAC + gate modul unit).
 *
 * Fitur yang TIDAK terdaftar di tabel ini diperlakukan sebagai production
 * (fail-open). Ini disengaja: kalau fail-closed, setiap halaman yang lupa
 * didaftarkan akan hilang dan terlihat seperti outage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();

            // Kode unik fitur. Bebas, tetapi disarankan mengikuti nama route
            // atau pola modul.aksi, mis. 'spd.dalam_kota' atau 'lpj.create'.
            $table->string('code', 120)->unique();
            $table->string('name', 150);
            $table->string('description', 255)->nullable();

            // Pengelompokan opsional ke modul siklus, untuk tampilan UI.
            // Catatan: AFTER tidak dipakai di sini — klausa itu hanya valid
            // untuk ALTER TABLE, bukan di dalam CREATE TABLE.
            $table->foreignId('module_id')->nullable()
                ->constrained('modules')->nullOnDelete();

            $table->enum('stage', ['development', 'production'])->default('development');

            // Jejak siapa & kapan menaikkan ke production.
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('activated_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index('stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
