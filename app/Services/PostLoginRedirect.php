<?php

namespace App\Services;

use App\Models\User;

/**
 * Menentukan halaman tujuan setelah login berhasil.
 *
 * Diekstrak dari AuthentiactionController agar login lokal dan login SSO
 * memakai aturan yang SAMA — kalau logikanya disalin, keduanya akan berbeda
 * begitu salah satu diubah.
 *
 * Ditambah satu penjaga: user yang sama sekali tidak punya permission
 * (kasus khas user SSO yang mapping-nya belum diisi) diarahkan ke halaman
 * profil, bukan ke dashboard yang akan menolaknya dengan 403.
 */
class PostLoginRedirect
{
    public function routeFor(User $user): string
    {
        // Nol permission -> tidak ada dashboard yang bisa dibuka. Profil selalu
        // dapat diakses oleh user yang sudah login, sehingga user tetap punya
        // halaman untuk membaca pesan.
        if ($user->getPermissionCodes() === []) {
            return route('profile');
        }

        // SEMUA role mendarat di All Modules lebih dulu bila punya lebih dari
        // satu modul. Tujuannya: user memilih modul secara sadar, bukan masuk
        // otomatis ke satu halaman yang mungkin bukan pekerjaannya.
        //
        // Syarat `> 1` penting: instalasi yang belum menjalankan ModuleSeeder
        // (tabel modules kosong) atau user yang hanya punya satu modul tetap
        // memakai alur lama, sehingga tidak ada layar kosong.
        $moduleContext = app(ModuleContext::class);
        $availableModules = $moduleContext->availableModules($user);

        if ($availableModules->count() > 1) {
            return route('modules.index');
        }

        return match ($this->effectiveRole($user)) {
            // Driver mendarat di dashboard-nya sendiri: ringkasan pekerjaan
            // pribadi (permohonan BBM, SPD/LPJ, pengecekan kendaraan).
            // Dashboard analitik unit tetap milik superadmin/GA/manager.
            'requester' => route('driver.dashboard'),
            'general_affairs' => route('admin-dashboard'),
            'dispatcher' => route('dispatcher.dispatcher-dashboard'),
            'manager' => route('superadmin.dashboard.bisnis'),
            'superadmin' => route('superadmin-dashboard'),
            // Pegawai (non-GA) / pengguna yang berangkat: halaman pertama yang
            // berguna adalah daftar validasi perjalanan dinas miliknya.
            'pegawai', 'pengguna_spd' => route('lpj.pengguna.index'),
            default => route('profile'),
        };
    }

    /**
     * Normalisasi legacy role column ke kode RBAC, dengan fallback ke pivot
     * user_roles bila kolom lama tidak meyakinkan.
     */
    private function effectiveRole(User $user): string
    {
        $effective = match ($user->role) {
            'admin', 'admin_bbm', 'general_affairs' => 'general_affairs',
            'driver', 'requester' => 'requester',
            'non_ga_staff', 'view_only', '' => 'view_only',
            default => $user->role,
        };

        if (in_array($effective, ['view_only', ''], true)) {
            $codes = $user->roles()->pluck('code')->all();

            foreach (['superadmin', 'general_affairs', 'manager', 'dispatcher', 'requester', 'pegawai', 'pengguna_spd'] as $candidate) {
                if (in_array($candidate, $codes, true)) {
                    return $candidate;
                }
            }
        }

        return (string) $effective;
    }
}
