<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun Developer — untuk mengaktifkan/menonaktifkan tahap fitur
 * (development <-> production), sesuai item 6.
 *
 * Berbeda dari Superadmin Pusat: akun ini HANYA memegang role `developers`
 * dengan permission `features.manage`, sehingga tidak bisa menyentuh RBAC,
 * unit, SSO mapping, atau gate modul per unit. Cakupannya sengaja sempit.
 *
 * Username sengaja sama dengan kode role-nya ('developers') karena diminta
 * untuk keperluan pengujian — bukan konvensi yang disarankan untuk produksi.
 *
 * CATATAN KEAMANAN: password default ('developers') LEMAH. Ganti sebelum
 * dipakai di produksi.
 */
class DeveloperUserSeeder extends Seeder
{
    private const USERNAME = 'developers';

    private const DEFAULT_PASSWORD = 'developers';

    public function run(): void
    {
        $unit = Unit::where('code', 'PUSAT')->first();
        $site = $unit
            ? (Site::where('code', 'PMU')->first() ?? $unit->sites()->orderBy('id')->first())
            : null;

        $user = User::updateOrCreate(
            ['username' => self::USERNAME],
            [
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'nama_lengkap' => 'Developer SIAGA',
                'role' => 'developers',
                'jabatan' => 'Developer',
                'email' => 'developers@geodipa.com',
                'default_site_id' => $site?->id,
                'unit_id' => $unit?->id,
                'is_active' => true,
            ]
        );

        // auth_provider di luar $fillable, diset langsung supaya akun ini
        // tetap jalur lokal (tidak bergantung SSO).
        if ($user->auth_provider !== 'local') {
            $user->auth_provider = 'local';
            $user->save();
        }

        if (! $user->user_code || str_starts_with((string) $user->user_code, 'USR-')) {
            $user->user_code = 'DEV.'.str_pad($user->id, 4, '0', STR_PAD_LEFT).'.'.($site?->code ?? 'PMU');
            $user->save();
        }

        $role = Role::where('code', 'developers')->first();

        if ($role) {
            // Ganti seluruh role (bukan menumpuk) supaya akun ini BENAR-BENAR
            // hanya punya hak developers, tidak lebih.
            $user->assignRole($role);
        } else {
            $this->command?->warn('Role developers belum ada — jalankan RbacSeeder lebih dulu.');
        }

        $this->command?->info(
            'Developer siap: '.self::USERNAME.' / '.self::DEFAULT_PASSWORD
            .' (unit '.($unit?->code ?? 'tanpa unit').') — ganti password sebelum produksi.'
        );
    }
}
