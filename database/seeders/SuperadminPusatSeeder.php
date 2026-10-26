<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun Superadmin Pusat.
 *
 * Peran akun ini ada dua:
 *
 *   1. Admin Pusat — karena unitnya PUSAT (is_central = true), akun ini
 *      melewati gate modul per unit dan dapat mengelola matriks modul serta
 *      pemetaan jabatan SSO. Itu memang yang dibutuhkan Pusat untuk mengawasi
 *      seluruh unit.
 *
 *   2. BREAK-GLASS ACCOUNT — sengaja dibuat dengan auth_provider = 'local'
 *      sehingga TIDAK bergantung pada SSO. Kalau IdP mati atau salah
 *      konfigurasi, ini satu-satunya jalan masuk. Karena itu:
 *        - jangan dihapus walau SSO sudah berjalan penuh,
 *        - jangan dihubungkan ke identitas SSO,
 *        - simpan kredensialnya di tempat terkontrol, bukan di repo.
 *
 * CATATAN KEAMANAN: password default di bawah ('superadmin') SANGAT LEMAH
 * untuk akun yang punya bypass gate modul penuh. WAJIB diganti sebelum
 * dipakai di produksi.
 *
 * CATATAN PENAMAAN: username memakai garis bawah, bukan spasi atau titik.
 * Form login memvalidasi dengan aturan `alpha_dash` yang MENOLAK spasi dan
 * titik — sudah diuji: 'superadmin.pusat' dan 'superadmin pusat' ditolak,
 * hanya 'superadmin_pusat' yang lolos.
 */
class SuperadminPusatSeeder extends Seeder
{
    private const USERNAME = 'superadmin_pusat';

    private const DEFAULT_PASSWORD = 'superadmin';

    public function run(): void
    {
        $unit = Unit::where('code', 'PUSAT')->first();

        if (! $unit) {
            $this->command?->warn('Unit PUSAT tidak ditemukan — jalankan UnitSeeder lebih dulu. Seeder dilewati.');

            return;
        }

        // Pastikan unit PUSAT ditandai central; kalau tidak, akun ini tidak
        // akan mendapat bypass gate modul dan tidak bisa mengelola konfigurasi.
        if (! $unit->is_central) {
            $unit->is_central = true;
            $unit->save();
        }

        $site = Site::where('code', 'PMU')->first()
            ?? $unit->sites()->orderBy('id')->first();

        $user = User::updateOrCreate(
            ['username' => self::USERNAME],
            [
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'nama_lengkap' => 'Super Admin Pusat',
                'role' => 'superadmin',
                'jabatan' => 'Super Administrator Pusat',
                'email' => 'superadmin.pusat@geodipa.com',
                'default_site_id' => $site?->id,
                'unit_id' => $unit->id,
                'is_active' => true,
            ]
        );

        // auth_provider di luar $fillable (agar tidak bisa diubah lewat form),
        // jadi harus di-set langsung. Ditegaskan di sini supaya akun ini
        // TIDAK pernah berpindah ke jalur SSO.
        if ($user->auth_provider !== 'local') {
            $user->auth_provider = 'local';
            $user->save();
        }

        // Kode user mengikuti format yang dipakai Manajemen User:
        // {prefix role}.{urutan}.{kode lokasi}
        if (! $user->user_code || str_starts_with((string) $user->user_code, 'USR-')) {
            $user->user_code = 'S00.'.str_pad($user->id, 4, '0', STR_PAD_LEFT).'.'.($site?->code ?? 'PMU');
            $user->save();
        }

        // Satu user = satu role RBAC (source of truth permission).
        $role = Role::where('code', 'superadmin')->first();

        if ($role) {
            // Lewat assignRole() agar jalur penugasan role tetap satu pintu.
            $user->assignRole($role, replace: false);
        } else {
            $this->command?->warn('Role superadmin belum ada — jalankan RbacSeeder lebih dulu.');
        }

        $this->command?->info(
            'Superadmin Pusat siap: '.self::USERNAME.' (unit '.$unit->code.', '
            .($user->auth_provider).') — ganti password default sebelum produksi.'
        );
    }
}
