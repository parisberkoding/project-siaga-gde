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
        $title = "Login Siaga Patuha";
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

        if (!Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 900);
            return back()->withErrors([
                'password' => 'Password yang Anda masukkan salah.'
            ])->withInput($request->only('username'));
        }

        if (isset($user->is_active) && !$user->is_active) {
            return back()->withErrors([
                'username' => 'Akun Anda tidak aktif. Hubungi administrator.'
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
     * Tentukan route default berdasarkan role (dipakai sebagai fallback intended)
     */
    protected function defaultRouteForRole(User $user): string
    {
        // Normalisasi legacy role column ke kode RBAC
        $effectiveRole = match ($user->role) {
            'admin', 'admin_bbm', 'general_affairs' => 'general_affairs',
            'driver', 'requester'                   => 'requester',
            'non_ga_staff', 'view_only', ''         => 'view_only',
            default                                 => $user->role,
        };

        // Cek RBAC pivot kalau legacy role tidak cocok
        if (in_array($effectiveRole, ['view_only', ''])) {
            $rbacCodes = $user->roles()->pluck('code')->all();
            if (in_array('superadmin', $rbacCodes)) $effectiveRole = 'superadmin';
            elseif (in_array('general_affairs', $rbacCodes)) $effectiveRole = 'general_affairs';
            elseif (in_array('manager', $rbacCodes)) $effectiveRole = 'manager';
            elseif (in_array('dispatcher', $rbacCodes)) $effectiveRole = 'dispatcher';
            elseif (in_array('requester', $rbacCodes)) $effectiveRole = 'requester';
        }

        return match($effectiveRole) {
            'requester'        => route('requester-index'),
            'general_affairs'  => route('admin-dashboard'),
            'dispatcher'       => route('dispatcher.dispatcher-dashboard'),
            'manager'          => route('superadmin.dashboard.bisnis'),
            'superadmin'       => route('superadmin-dashboard'),
            default            => route('profile'),
        };
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
