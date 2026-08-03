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
     * Change password - versi simple (langsung ubah aja)
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'new_password' => [
                'required',
                'string',
                'confirmed',
                'min:8'
            ],
        ], [
            'new_password.required' => 'Password baru harus diisi',
            'new_password.confirmed' => 'Konfirmasi password tidak cocok',
            'new_password.min' => 'Password minimal 8 karakter',
        ]);

        $user = Auth::user();

        // Langsung update password aja, tanpa cek password lama
        $user->password = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'Password berhasil diubah!');
    }
}
