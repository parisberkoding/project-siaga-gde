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
            'password' => 'required|string|min:3|max:25'
        ], [
            'username.required'   => 'Username harus diisi.',
            'username.alpha_dash' => 'Username tidak valid.',
            'password.required'   => 'Password harus diisi.',
            'password.min'        => 'Password minimal 3 karakter.'
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

        $request->session()->regenerate(true);

        Auth::login($user, $request->filled('remember'));

        return $this->redirectBasedOnRole($user);
    }

    /**
     * Redirect user berdasarkan role
     */
    protected function redirectBasedOnRole(User $user): \Illuminate\Http\RedirectResponse
    {
        $name = e($user->nama_lengkap);

        return match($user->role) {
            'requester' => redirect()->route('requester-index')
                ->with('success', "Login Berhasil, Selamat datang {$name}."),

            'admin' => redirect()->route('admin-dashboard')
                ->with('success', "Login Berhasil, Selamat datang {$name}."),

            'dispatcher' => redirect()->route('dispatcher.dispatcher-dashboard')
                ->with('success', "Login Sukses, Selamat datang {$name}!"),

            'manager' => redirect()->route('superadmin.dashboard.bisnis')
                ->with('success', "Login Sukses, Selamat datang {$name}!"),

            'superadmin' => redirect()->route('superadmin-dashboard')
                ->with('success', "Login Sukses, Selamat datang {$name}!"),

            'non_ga_staff' => redirect()->route('profile')
                ->with('success', "Login Berhasil, Selamat datang {$name}."),

            default => redirect()->intended('/home')
                ->with('success', "Selamat datang, {$name}!"),
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
