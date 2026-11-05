<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;

class AuthentiactionController extends Controller
{
    /**
     * Display login page
     */
    public function index()
    {
        $title = "Login SIAGA";
        return view('authentication.login', compact('title'));
    }

    /**
     * Proses login user dengan validasi dinamis
     * Dilindungi dari XSS, SQL Injection, Brute Force, Session Fixation
     */
    public function authenticate(Request $request)
    {
        $throttleKey = Str::lower($request->input('username', '')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);
            return back()->withErrors([
                'username' => "Terlalu banyak percobaan login. Coba lagi dalam {$minutes} menit."
            ])->withInput($request->only('username'));
        }

        $validated = $request->validate([
            'username' => 'required|string|max:30|alpha_dash',
            'password' => 'required|string|min:1|max:100'
        ], [
            'username.required'   => 'Username harus diisi.',
            'username.alpha_dash' => 'Username tidak valid.',
            'password.required'   => 'Password harus diisi.'
        ]);

        $user = User::where('username', strip_tags($validated['username']))->first();

        if (!$user) {
            RateLimiter::hit($throttleKey, 900);
            return back()->withErrors([
                'username' => 'Username tidak terdaftar dalam sistem.'
            ])->withInput($request->only('username'));
        }

        // User ber-provider SSO DILARANG login lewat form lokal.
        //
        // Tanpa penolakan ini, pencabutan akses di SSO tidak ada artinya selama
        // password lokalnya masih hidup — deprovisioning menjadi ilusi.
        // Diperiksa SEBELUM verifikasi password supaya jalur ini tidak pernah
        // bergantung pada benar/salahnya password.
        if ($user->usesSso()) {
            return back()->withErrors([
                'username' => 'Akun ini menggunakan SSO Geodipa. Silakan login melalui tombol SSO.'
            ])->withInput($request->only('username'));
        }

        if (!Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 900);
            return back()->withErrors([
                'password' => 'Password yang Anda masukkan salah.'
            ])->withInput($request->only('username'));
        }

        // Akun nonaktif. Pesannya dibedakan dari "password salah" agar user
        // tidak mengira dirinya salah memasukkan password.
        if (! $user->isActiveAccount()) {
            return back()->withErrors([
                'username' => 'Akun Anda telah dinonaktifkan. Hubungi Superadmin Pusat.'
            ])->withInput($request->only('username'));
        }

        RateLimiter::clear($throttleKey);

        // Simpan intended URL sebelum invalidate, supaya redirect()->intended() tetap jalan
        $intended = $request->session()->get('url.intended');

        // Invalidate session lama (anti session-fixation: hapus semua data + rotate ID)
        $request->session()->invalidate();

        // Restore intended URL ke session baru
        if ($intended) {
            $request->session()->put('url.intended', $intended);
        }

        Auth::login($user, $request->filled('remember'));

        // Redirect ke halaman yang user mau akses sebelum login, fallback ke dashboard role
        return redirect()->intended($this->defaultRouteForRole($user))
            ->with('success', "Login Berhasil, Selamat datang {$user->nama_lengkap}.");
    }

    /**
     * Tentukan route default setelah login.
     *
     * Logikanya dipindah ke PostLoginRedirect agar login lokal dan login SSO
     * memakai aturan yang sama.
     */
    protected function defaultRouteForRole(User $user): string
    {
        return app(\App\Services\PostLoginRedirect::class)->routeFor($user);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): \Illuminate\Http\RedirectResponse
    {
        Auth::logout();

        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withoutCookie(config('session.cookie'))
            ->with('success', 'Anda telah berhasil logout.');
    }
}
