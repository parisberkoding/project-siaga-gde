<?php

namespace App\Http\Controllers;

use App\Models\SsoSyncLog;
use App\Services\PostLoginRedirect;
use App\Services\Sso\Contracts\SsoProvider;
use App\Services\Sso\SsoAuthenticator;
use App\Services\Sso\SsoException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Titik masuk SSO Geodipa.
 *
 * Controller ini sengaja TIDAK mengetahui protokol apa pun. Tugasnya hanya:
 *   1. mengarahkan user ke IdP (menyertakan state anti-CSRF),
 *   2. menerima callback, memvalidasi state,
 *   3. menyerahkan klaim yang sudah dinormalisasi ke SsoAuthenticator,
 *   4. membuat sesi Laravel bila hasilnya mengizinkan.
 *
 * Begitu IT Pusat menetapkan protokolnya, yang ditambahkan hanya satu class
 * adapter di App\Services\Sso\Providers — controller ini tidak berubah.
 */
class SsoController extends Controller
{
    private const SESSION_STATE = 'sso_state';

    /**
     * Mulai alur login SSO.
     */
    public function redirect(Request $request, SsoProvider $provider): RedirectResponse
    {
        if (! config('sso.enabled', false)) {
            return redirect()->route('login')
                ->with('error', 'Login SSO belum diaktifkan. Silakan gunakan login biasa.');
        }

        if (! $provider->isConfigured()) {
            return redirect()->route('login')
                ->with('error', SsoException::notConfigured()->getMessage());
        }

        // State disimpan di session dan diminta kembali oleh IdP. Ini yang
        // mencegah callback palsu dari pihak lain.
        $state = Str::random(40);
        $request->session()->put(self::SESSION_STATE, $state);

        try {
            return redirect()->away($provider->redirectUrl($state));
        } catch (SsoException $e) {
            $request->session()->forget(self::SESSION_STATE);

            return redirect()->route('login')->with('error', $e->getMessage());
        }
    }

    /**
     * Terima callback dari IdP.
     */
    public function callback(
        Request $request,
        SsoProvider $provider,
        SsoAuthenticator $authenticator,
        PostLoginRedirect $redirects
    ): RedirectResponse {
        if (! config('sso.enabled', false)) {
            return redirect()->route('login')
                ->with('error', 'Login SSO belum diaktifkan.');
        }

        // ── Validasi state ────────────────────────────────────────────────
        $expectedState = $request->session()->pull(self::SESSION_STATE);
        $returnedState = (string) $request->input('state', '');

        if (! $expectedState || ! hash_equals($expectedState, $returnedState)) {
            SsoSyncLog::record(
                SsoSyncLog::DECISION_REJECTED,
                subject: null,
                rawClaims: null,
                note: 'State SSO tidak cocok atau sudah kedaluwarsa (kemungkinan callback palsu / sesi kedaluwarsa).',
                provider: $provider->name(),
            );

            return redirect()->route('login')->with('error', SsoException::invalidState()->getMessage());
        }

        // ── Tukar kode menjadi klaim ter-normalisasi ──────────────────────
        try {
            $info = $provider->handleCallback($request);
        } catch (SsoException $e) {
            return redirect()->route('login')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            // Detail teknis TIDAK ditampilkan ke user; cukup dicatat.
            report($e);

            SsoSyncLog::record(
                SsoSyncLog::DECISION_REJECTED,
                subject: null,
                rawClaims: null,
                note: 'Gagal memproses callback SSO: '.$e->getMessage(),
                provider: $provider->name(),
            );

            return redirect()->route('login')
                ->with('error', 'Terjadi gangguan saat memproses login SSO. Silakan coba lagi atau hubungi admin.');
        }

        // ── Provisioning & pemetaan hak akses ─────────────────────────────
        try {
            $result = $authenticator->authenticate($info);
        } catch (SsoException $e) {
            return redirect()->route('login')->with('error', $e->getMessage());
        }

        if (! $result->canLogin() || ! $result->user) {
            return redirect()->route('login')
                ->with('error', $result->message ?? 'Login SSO tidak dapat diproses.');
        }

        // ── Buat sesi ─────────────────────────────────────────────────────
        $user = $result->user;
        $intended = $request->session()->get('url.intended');

        // Rotasi session id (anti session-fixation), sama seperti login lokal.
        $request->session()->invalidate();

        if ($intended) {
            $request->session()->put('url.intended', $intended);
        }

        // Device fingerprint SENGAJA tidak di-set di sini: middleware
        // VerifyDeviceFingerprint yang akan mengisinya pada request berikutnya,
        // supaya sidik jari dihitung dari request normal — bukan dari redirect
        // balik IdP yang bisa datang lewat jaringan/proxy berbeda.
        Auth::login($user, remember: false);

        $target = $result->needsReviewFlag()
            ? route('profile')
            : $redirects->routeFor($user);

        $response = redirect()->intended($target);

        if ($result->needsReviewFlag()) {
            return $response->with('warning', $result->message);
        }

        return $response->with('success', "Login SSO berhasil. Selamat datang {$user->nama_lengkap}.");
    }
}
