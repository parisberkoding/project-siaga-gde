<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pastikan akun yang sedang login masih aktif.
 *
 * Tanpa middleware ini, menonaktifkan akun TIDAK langsung berlaku: sesi
 * Laravel yang sudah berjalan (default 120 menit) akan terus hidup sampai
 * kedaluwarsa sendiri. Untuk user SSO ini penting, karena pencabutan akses di
 * IdP tidak otomatis mematikan sesi lokal.
 *
 * Akun yang nonaktif dipaksa logout, bukan sekadar diberi 403 — supaya tidak
 * ada sesi "setengah hidup" yang terus menembak request.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        if ($user->isActiveAccount()) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('error', 'Akun Anda telah dinonaktifkan. Hubungi Superadmin Pusat.');
    }
}
