<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kredensial akun hasil auto-registrasi scan.
 *
 * Alur yang disepakati:
 *   - pegawai non-SSO yang scan QR dibuatkan akun User (nonaktif, belum punya
 *     role) + EmployeeDefault;
 *   - superadmin unit membuat username & password final, lalu memberikannya
 *     secara offline;
 *   - superadmin masih boleh melihat password tersebut beberapa kali, jadi
 *     salinan terenkripsi (APP_KEY) disimpan terpisah dari hash login.
 *
 * `users.password` tetap hash satu arah untuk autentikasi. `credentials_plain`
 * hanyalah salinan terenkripsi untuk kebutuhan operasional dan setiap
 * pembukaan dicatat di `user_credential_reveals`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'credentials_plain')) {
                $table->text('credentials_plain')->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'credentials_set_at')) {
                $table->timestamp('credentials_set_at')->nullable()->after('credentials_plain');
            }
            if (! Schema::hasColumn('users', 'credentials_set_by')) {
                $table->foreignId('credentials_set_by')->nullable()->after('credentials_set_at')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'credentials_reveal_count')) {
                $table->unsignedInteger('credentials_reveal_count')->default(0)->after('credentials_set_by');
            }
            if (! Schema::hasColumn('users', 'credentials_last_revealed_at')) {
                $table->timestamp('credentials_last_revealed_at')->nullable()->after('credentials_reveal_count');
            }
            if (! Schema::hasColumn('users', 'registration_source')) {
                $table->string('registration_source', 20)->default('admin')->after('credentials_last_revealed_at');
            }
        });

        if (! Schema::hasTable('user_credential_reveals')) {
            Schema::create('user_credential_reveals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('revealed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('revealed_at')->useCurrent();
                $table->index(['user_id', 'revealed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_credential_reveals');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'registration_source')) {
                $table->dropColumn('registration_source');
            }
            if (Schema::hasColumn('users', 'credentials_last_revealed_at')) {
                $table->dropColumn('credentials_last_revealed_at');
            }
            if (Schema::hasColumn('users', 'credentials_reveal_count')) {
                $table->dropColumn('credentials_reveal_count');
            }
            if (Schema::hasColumn('users', 'credentials_set_by')) {
                $table->dropConstrainedForeignId('credentials_set_by');
            }
            if (Schema::hasColumn('users', 'credentials_set_at')) {
                $table->dropColumn('credentials_set_at');
            }
            if (Schema::hasColumn('users', 'credentials_plain')) {
                $table->dropColumn('credentials_plain');
            }
        });
    }
};
