<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission baru: `lpj.pencairan`.
 *
 * Dipakai untuk gerbang hak akses pengelolaan pencairan dana perjalanan dinas
 * (Lembar Lampiran Pemberian Dana) yang HANYA boleh dilakukan/diunduh oleh sisi
 * General Affairs (dan superadmin).
 *
 * Mapping yang dijaga di sini:
 *   1. PermissionGroup : lpj          → halaman "LPJ"
 *   2. Module          : spd          → gerbang modul Perjalanan Dinas (SPD & LPJ)
 *   3. Role            : general_affairs (+ superadmin) → pemilik hak
 *
 * Idempotent: aman dijalankan ulang (updateOrCreate + sync tanpa detach role lain).
 */
return new class extends Migration
{
    private const PERMISSION_CODE = 'lpj.pencairan';

    public function up(): void
    {
        $group = PermissionGroup::firstOrCreate(
            ['code' => 'lpj'],
            ['name' => 'LPJ', 'icon' => 'bi bi-receipt', 'order' => 4]
        );

        $permission = Permission::updateOrCreate(
            ['code' => self::PERMISSION_CODE],
            [
                'permission_group_id' => $group->id,
                'module'              => 'lpj',
                'action'              => 'pencairan',
                'description'         => 'Mengelola & mengunduh lampiran pencairan dana perjalanan dinas (Divisi GA)',
            ]
        );

        // ── Role: General Affairs + Superadmin ─────────────────────────────
        $roleCodes = ['general_affairs', 'superadmin'];

        foreach (Role::whereIn('code', $roleCodes)->get() as $role) {
            if (! DB::table('role_permissions')
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
        }

        // ── Module: Perjalanan Dinas (SPD & LPJ) ──────────────────────────
        $module = Module::where('code', 'spd')->first();

        if ($module && ! DB::table('module_permissions')
            ->where('module_id', $module->id)
            ->where('permission_id', $permission->id)
            ->exists()) {
            DB::table('module_permissions')->insert([
                'module_id'     => $module->id,
                'permission_id' => $permission->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void
    {
        $permission = Permission::where('code', self::PERMISSION_CODE)->first();

        if ($permission) {
            DB::table('role_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('module_permissions')->where('permission_id', $permission->id)->delete();
            $permission->delete();
        }
    }
};
