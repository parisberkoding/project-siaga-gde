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

        return match ($this->effectiveRole($user)) {
            'requester' => route('requester-index'),
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
