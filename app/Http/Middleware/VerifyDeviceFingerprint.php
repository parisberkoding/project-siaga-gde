<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deteksi session hijacking berbasis device fingerprint.
 *
 * Setelah login, sidik jari (hash dari User-Agent + IP) disimpan di session.
 * Setiap request autentikasi diverifikasi: bila sidik jari berubah drastis
 * (mis. sesi dicuri dan dipakai dari browser/jaringan lain), sesi di-invalidate
 * dan user dipaksa login ulang.
 */
class VerifyDeviceFingerprint
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $fingerprint = $this->fingerprintFor($request);
        $stored = $request->session()->get('device_fingerprint');

        if ($stored === null) {
            // Sesi dari versi lama (belum ada sidik jari) → set sekarang.
            $request->session()->put('device_fingerprint', $fingerprint);

            return $next($request);
        }

        if (! hash_equals($stored, $fingerprint)) {
            Auth::logout();
            $request->session()->flush();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Sesi Anda dianggap mencurigakan. Silakan login kembali.');
        }

        return $next($request);
    }

    protected function fingerprintFor(Request $request): string
    {
        $ua = $request->userAgent() ?? '';

        // IP dinormalisasi ke /24 (IPv4) agar pergeseran IP kecil akibat
        // DHCP/carrier tidak memicu logout palsu, tapi pergantian jaringan
        // atau browser tetap terdeteksi.
        $ip = $request->ip() ?? '';
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $ip = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
        }

        // Hash deterministik (sha256) supaya bisa dibandingkan via hash_equals;
        // bcrypt tidak cocok dipakai karena salt acak membuat hasil selalu beda.
        return hash('sha256', "{$ua}|{$ip}");
    }
}
