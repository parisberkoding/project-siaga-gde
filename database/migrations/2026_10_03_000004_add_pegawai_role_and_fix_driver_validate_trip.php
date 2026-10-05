<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Role `pegawai` + perbaikan hak validasi perjalanan untuk driver.
 *
 * Dua perubahan:
 *
 * 1. Role `requester` (Driver / Requester) TIDAK lagi memegang
 *    `spd.validate_trip`. Sebelumnya driver melihat menu "Validasi Perjalanan
 *    (User)" di sidebar, padahal validasi kebenaran perjalanan adalah tugas
 *    pengguna yang ikut berangkat (penumpang), bukan pengemudi.
 *
 * 2. Role baru `pegawai` — pegawai non-GA, target pemetaan jabatan SSO:
 *    dashboard.view, spd.view, spd.validate_trip, lpj.view.
 *    Apakah nanti mendapat hak tulis (CRUD) ditentukan oleh role hasil mapping
 *    `sso_role_mappings` (jabatan + unit), bukan oleh migrasi ini.
 *
 * Idempotent: aman dijalankan berulang.
 */
return new class extends Migration
{
    private const ROLE_CODE = 'pegawai';

    /** @var array<int, string> */
    private const PERMISSIONS = [
        'dashboard.view',
        'spd.view',
        'spd.validate_trip',
        'lpj.view',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        $this->buatRolePegawai();
        $this->cabutValidasiDariDriver();
    }

    public function down(): void
    {
        // Kembalikan hak validasi ke driver (perilaku lama).
        $role = Role::where('code', 'requester')->first();
        $permission = Permission::where('code', 'spd.validate_trip')->first();

        if ($role && $permission && ! DB::table('role_permissions')
            ->where('role_id', $role->id)
            ->where('permission_id', $permission->id)
            ->exists()) {
            DB::table('role_permissions')->insert([
                'role_id'       => $role->id,
                'permission_id' => $permission->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        $pegawai = Role::where('code', self::ROLE_CODE)->first();

        if ($pegawai) {
            DB::table('role_permissions')->where('role_id', $pegawai->id)->delete();
            DB::table('user_roles')->where('role_id', $pegawai->id)->delete();
            $pegawai->delete();
        }
    }

    private function buatRolePegawai(): void
    {
        $role = Role::updateOrCreate(
            ['code' => self::ROLE_CODE],
            [
                'unit_id'     => null, // role global — berlaku untuk semua unit
                'name'        => 'Pegawai',
                'description' => 'Pegawai non-GA: melihat surat tugas & LPJ, serta memvalidasi kebenaran '
                    .'perjalanan dinas sebagai pengguna yang berangkat (tahap 1). '
                    .'Target pemetaan jabatan SSO untuk pegawai umum.',
                'is_active'   => true,
                'is_system'   => false,
            ]
        );

        $permissionIds = Permission::whereIn('code', self::PERMISSIONS)->pluck('id')->all();

        $role->permissions()->sync($permissionIds);
    }

    private function cabutValidasiDariDriver(): void
    {
        $role = Role::where('code', 'requester')->first();
        $permission = Permission::where('code', 'spd.validate_trip')->first();

        if (! $role || ! $permission) {
            return;
        }

        DB::table('role_permissions')
            ->where('role_id', $role->id)
            ->where('permission_id', $permission->id)
            ->delete();
    }
};
