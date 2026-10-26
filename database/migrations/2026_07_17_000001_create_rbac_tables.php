<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 2 - RBAC Refactor
     *
     * Menambahkan Authorization Layer tanpa menghapus kolom `role` eksisting
     * (strategi dual-write): tabel RBAC baru dibangun di samping, lalu di-seed
     * dari nilai role lama agar aplikasi tidak breaking.
     */
    public function up(): void
    {
        // 1. Permission Groups (pengelompokan UX agar admin tidak menghadapi
        //    puluhan permission dalam satu daftar panjang)
        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();      // bbm, spd, lpj, vehicle, users, settings
            $table->string('name');               // BBM, SPD, LPJ, Kendaraan, Users, Settings
            $table->string('icon')->nullable();   // bi bi-fuel-pump (untuk sidebar)
            $table->integer('order')->default(99);
            $table->timestamps();
        });

        // 2. Permissions (granular: module.action)
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();     // bbm.request, spd.create, lpj.approve
            $table->foreignId('permission_group_id')->nullable()->constrained('permission_groups')->nullOnDelete();
            $table->string('module');             // bbm, spd, lpj, vehicle, users, settings
            $table->string('action');             // view, create, update, delete, approve, export
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['module', 'action']);
        });

        // 3. Roles (kumpulan permission, TIDAK lagi menentukan akses langsung)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();     // superadmin, general_affairs, dispatcher, requester
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // role bawaan, tidak bisa dihapus
            $table->timestamps();
        });

        // 4. Role - Permission pivot
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        // 5. User - Role pivot (dual-write: user tetap punya kolom `role`,
        //    tabel ini menjadi source of truth RBAC)
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('permission_groups');
    }
};
