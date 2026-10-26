<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integrasi SSO — fondasi.
 *
 * Konteks bisnis: hanya KARYAWAN TETAP yang punya SSO. Karyawan outsourcing
 * dan magang TIDAK punya SSO, sehingga akunnya dibuat manual oleh admin.
 * Karena itu `auth_provider` TIDAK boleh dipakai untuk menyimpulkan status
 * kepegawaian — outsourcing dan magang sama-sama `local`. Status kepegawaian
 * adalah atribut terpisah (employment_type) yang nanti diisi dari HR.
 *
 * Pembagian sumber kebenaran yang disepakati:
 *     SSO   -> autentikasi (siapa yang boleh login)
 *     HR    -> atribut kepegawaian (NIK, unit, jabatan, status)
 *     SIAGA -> otorisasi + Master Pekerja
 *
 * Desain ini sengaja provider-agnostic: protokol SSO (OIDC/OAuth2/SAML) dan
 * isi endpoint HR belum diputuskan IT Pusat, sehingga yang dibangun lebih dulu
 * adalah lapisan lokal yang tidak bergantung pada protokol.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Penanda autentikasi pada users ─────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            // local | sso — menentukan jalur login. Default `local` supaya
            // seluruh user eksisting tidak berubah perilakunya.
            $table->string('auth_provider', 20)->default('local')->after('role');

            // Menonaktifkan akun TANPA soft-delete. Soft-delete tidak dipakai
            // untuk ini karena user terikat ke permohonan, surat SPD, dan
            // transaksi lunch — menghapusnya berarti kehilangan histori.
            // Catatan: AuthentiactionController sudah lama memeriksa
            // `isset($user->is_active)` tetapi kolomnya belum pernah ada,
            // sehingga pemeriksaan itu selama ini tidak pernah berjalan.
            $table->boolean('is_active')->default(true)->after('auth_provider');
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
            $table->string('deactivation_reason', 255)->nullable()->after('deactivated_at');

            // Kapan terakhir disinkronkan dari SSO/HR.
            $table->timestamp('sso_synced_at')->nullable()->after('deactivation_reason');

            // NIK dari HR — kunci bridging ke Master Pekerja.
            // Nullable karena endpoint HR belum tersedia; untuk sementara
            // SIAGA memakai `user_code` internal sebagai identifier.
            $table->string('nik', 50)->nullable()->unique()->after('user_code');
        });

        // ── 2. NIK pada Master Pekerja (sisi lain jembatan) ───────────────
        Schema::table('employee_defaults', function (Blueprint $table) {
            $table->string('nik', 50)->nullable()->unique()->after('employee_code');

            // tetap | outsourcing | magang — diisi dari HR, BUKAN disimpulkan
            // dari ada/tidaknya SSO (magang juga tidak punya SSO).
            $table->string('employment_type', 20)->nullable()->after('division');
        });

        // ── 3. Identitas SSO ──────────────────────────────────────────────
        // Dipisah dari `users` supaya satu user bisa punya lebih dari satu
        // identitas provider, dan supaya `subject` (ID unik IdP) tetap stabil
        // walau atribut lain berubah.
        Schema::create('sso_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider', 50)->default('geodipa');
            $table->string('subject', 191);
            $table->json('claims')->nullable();       // raw claims terakhir
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'subject']);
            $table->index('user_id');
        });

        // ── 4. Pemetaan jabatan + unit -> role ────────────────────────────
        // Inilah "data kiriman SSO jabatannya Asisten Manager, lalu di tabel
        // yang disiapkan: Asisten Manager itu bisa mengakses apa".
        //
        // unit_id sengaja NOT NULL: mapping berlaku per kombinasi (jabatan x
        // unit), karena dua orang berjabatan sama di unit berbeda bisa butuh
        // hak akses berbeda. Konsekuensinya matrix-nya kecil dan terhitung
        // (3 jabatan x 3-4 unit = sekitar 12 baris), dan unique constraint
        // di bawah bisa ditegakkan DB tanpa jebakan NULL.
        //
        // jabatan_key = bentuk ternormalisasi (lowercase, spasi dirapikan)
        // yang dipakai untuk PENCOCOKAN, sedangkan `jabatan` menyimpan tulisan
        // aslinya untuk tampilan. Dipisah karena jabatan dari SSO adalah teks
        // bebas: "Asisten manager" dan "Asisten Manager" harus dianggap sama,
        // tanpa mengubah "SPV" menjadi "Spv" di layar.
        Schema::create('sso_role_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('jabatan', 100);
            $table->string('jabatan_key', 100);
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->unique(['jabatan_key', 'unit_id']);
        });

        // ── 5. Jejak sinkronisasi SSO ─────────────────────────────────────
        // Menyimpan RAW CLAIMS. Sangat berguna saat troubleshooting
        // ("waktu itu SSO mengirim apa?") karena atribut dari IdP tidak dapat
        // direproduksi setelah kejadian. Semua FK nullOnDelete supaya baris
        // audit tidak pernah hilang / memblokir penghapusan entitas lain.
        Schema::create('sso_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->default('geodipa');
            $table->string('subject', 191)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('raw_claims')->nullable();
            $table->string('decision', 50);            // mapped | unmapped | rejected | conflict | synced
            $table->unsignedBigInteger('mapped_role_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['subject', 'created_at']);
            $table->index('decision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_sync_logs');
        Schema::dropIfExists('sso_role_mappings');
        Schema::dropIfExists('sso_identities');

        Schema::table('employee_defaults', function (Blueprint $table) {
            $table->dropColumn(['nik', 'employment_type']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nik',
                'auth_provider',
                'is_active',
                'deactivated_at',
                'deactivation_reason',
                'sso_synced_at',
            ]);
        });
    }
};
