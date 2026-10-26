<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Show user profile page
     */
    public function index()
    {
        return view('profile.index');
    }

    /**
     * Change password - wajib verifikasi password lama untuk mencegah
     * pengambilalihan sesi, lalu regenerate session (anti session-fixation).
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'confirmed',
                'min:8'
            ],
        ], [
            'current_password.required' => 'Password lama harus diisi',
            'new_password.required' => 'Password baru harus diisi',
            'new_password.confirmed' => 'Konfirmasi password tidak cocok',
            'new_password.min' => 'Password minimal 8 karakter',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password lama tidak sesuai.',
            ])->withInput();
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        // Regenerate session agar sesi lama (yang mungkin bocor) tidak berlaku lagi
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back()->with('success', 'Password berhasil diubah!');
    }
}
