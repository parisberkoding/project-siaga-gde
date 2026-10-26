<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Services\ModuleContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "All Modules" — titik masuk setelah login.
 *
 * Satu halaman ini menggantikan sidebar raksasa milik superadmin. User
 * memilih satu siklus bisnis, lalu sidebar & route mengikuti modul tersebut.
 */
class ModuleController extends Controller
{
    public function __construct(private ModuleContext $context) {}

    /**
     * Daftar modul yang boleh dibuka user.
     *
     * Konteks modul sengaja dihapus saat halaman ini dibuka, sehingga user
     * benar-benar "keluar" dari modul sebelumnya dan sidebar tidak lagi
     * menampilkan menu modul lama.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $modules = $this->context->availableModules($user);

        $this->context->forget();

        return view('modules.index', [
            'title' => 'All Modules — SIAGA',
            'modules' => $modules,
            'user' => $user,
            'unit' => $user->unit,
            'isSuperadmin' => $this->context->isSuperadmin($user),
        ]);
    }

    /**
     * Buka satu modul, lalu arahkan ke halaman pertama yang sesuai dengan
     * permission user.
     */
    public function activate(Request $request, Module $module): RedirectResponse
    {
        abort_unless($module->is_active, 404);

        $user = $request->user();

        abort_unless(
            $this->context->userCanAccess($user, $module),
            403,
            'Anda tidak memiliki akses ke modul ini pada unit Anda.'
        );

        $this->context->set($module);

        $target = $this->context->routeForModule($module, $user);

        if (! $target) {
            $this->context->forget();

            return redirect()
                ->route('modules.index')
                ->with('error', "Modul \"{$module->name}\" belum memiliki halaman yang bisa Anda akses.");
        }

        return redirect($target)
            ->with('success', "Modul \"{$module->name}\" dibuka.");
    }
}
