<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gate Modul per Unit — fondasi.
 *
 * Latar belakang: IT Pusat meminta "modul mana yang di-enable untuk unit mana".
 * RBAC eksisting menjawab "siapa boleh apa" (global, per role), sedangkan tabel
 * di migrasi ini menjawab pertanyaan berbeda: "fitur apa yang ADA untuk unit ini".
 *
 * Keduanya adalah axis yang terpisah dan sengaja TIDAK digabung, karena
 * menggabungkan keduanya akan menghasilkan ledakan kombinatorial
 * (11 modul x 49 permission x N unit).
 *
 * Rumus otorisasi menjadi:
 *     efektif = punya Permission (RBAC) AND modul enabled untuk unit user
 *
 * Bypass: user yang unitnya is_central (Pusat) melewati gate modul, TETAPI
 * tetap tunduk pada RBAC — jadi Pusat tidak otomatis bisa segala hal.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Tandai unit Pusat ──────────────────────────────────────────
        // is_central menentukan bypass gate modul DAN bypass data scoping
        // (inUnit). Dipisahkan dari unit_id supaya "Pusat" tidak lagi
        // disimpulkan dari unit_id yang kosong.
        Schema::table('units', function (Blueprint $table) {
            $table->boolean('is_central')->default(false)->after('is_active');
        });

        DB::table('units')->where('code', 'PUSAT')->update(['is_central' => true]);

        // ── 2. Modul ─────────────────────────────────────────────────────
        // Satu modul = satu SIKLUS BISNIS utuh (input -> proses -> report ->
        // dashboard), bukan sekedar pengelompokan UI. Karena itu tabel ini
        // sengaja terpisah dari `permission_groups`.
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();     // bbm, spd, vehicle, lunch, ai, users, rbac, settings
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->string('icon', 60)->nullable();   // bi bi-fuel-pump (untuk sidebar/UI matrix)
            $table->integer('order')->default(99);

            // is_gateable  : boleh dimatikan per unit?
            // is_always_on : selalu aktif lintas unit (RBAC & Pengaturan)
            // default_enabled : nilai awal saat unit baru dibuat
            $table->boolean('is_gateable')->default(true);
            $table->boolean('is_always_on')->default(false);
            $table->boolean('default_enabled')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('order');
        });

        // ── 3. Pemetaan permission -> modul (many-to-many) ────────────────
        // Many-to-many diperlukan karena ada permission yang memang dipakai
        // lintas siklus. Contoh paling nyata: halaman /pelaporan menaungi DUA
        // mode (BBM operasional & SPD) di bawah satu permission yang sama.
        // Karena itu modul TIDAK boleh diturunkan dari prefix kode permission.
        Schema::create('module_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['module_id', 'permission_id']);
        });

        // ── 4. Matrix unit x modul (yang dichecklist Pusat) ───────────────
        Schema::create('unit_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['unit_id', 'module_id']);
        });

        // ── 5. Audit perubahan gate ───────────────────────────────────────
        // Belum ada infrastruktur audit di aplikasi ini, sedangkan mengubah
        // akses modul sebuah unit adalah tindakan sensitif. Semua FK dibuat
        // nullOnDelete supaya baris audit TIDAK pernah hilang / memblokir
        // penghapusan entitas lain.
        Schema::create('module_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->string('action', 20);              // enable | disable | bulk_enable | bulk_disable
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_access_logs');
        Schema::dropIfExists('unit_modules');
        Schema::dropIfExists('module_permissions');
        Schema::dropIfExists('modules');

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('is_central');
        });
    }
};
