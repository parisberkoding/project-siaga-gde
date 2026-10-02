<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi route ke superadmin unit PUSAT.
 *
 * Dipakai untuk konfigurasi yang mengubah hak akses banyak orang sekaligus
 * (pemetaan jabatan SSO). Superadmin unit biasa TIDAK boleh menyalakan atau
 * mengubah hak akses untuk unitnya sendiri — itu kewenangan Pusat.
 *
 * Catatan transisi: user yang belum punya unit (unit_id null) diperlakukan
 * sebagai global/Pusat, konsisten dengan User::bypassesModuleGate(). Setelah
 * backfill unit_id selesai, hanya is_central = true yang lolos.
 */
class EnsureCentralUnit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403, 'Anda harus login terlebih dahulu.');
        }

        if ($user->unit_id === null || $user->unitIsCentral()) {
            return $next($request);
        }

        abort(403, 'Hanya superadmin unit Pusat yang dapat mengubah konfigurasi hak akses.');
    }
}
