<?php

namespace App\Http\Middleware;

use App\Models\Module;
use App\Services\ModuleContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureModuleContext — penjaga isolasi modul.
 *
 * Setelah user memilih modul di halaman All Modules, seluruh request hanya
 * boleh menyentuh route milik modul tersebut. Route lintas modul (profil,
 * dashboard, file, logout) tetap terbuka.
 *
 * Bila user mencoba membuka route modul lain, request dialihkan kembali ke
 * All Modules dengan pesan yang menyebut modul asal & tujuan. Untuk request
 * AJAX, 403 dipakai supaya JavaScript tidak menerima HTML halaman redirect.
 *
 * Tanpa modul aktif di session, middleware ini TIDAK mengetatkan apa pun —
 * perilaku lama (dashboard per role) tetap berjalan. Ini juga menjadi jalur
 * masuk deep link: route yang jelas bermodul akan otomatis membuka modulnya.
 */
class EnsureModuleContext
{
    public function __construct(private ModuleContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (! $routeName || $this->context->isSharedRoute($routeName)) {
            return $next($request);
        }

        $targetModuleCode = $this->context->moduleForRoute($routeName);
        $activeModule = $this->context->current();

        // Belum ada modul aktif: buka modul yang jelas memiliki route ini
        // (deep link / bookmark) alih-alih memaksa user lewat All Modules.
        if (! $activeModule) {
            $module = $targetModuleCode
                ? Module::query()->active()->where('code', $targetModuleCode)->first()
                : null;

            if ($module && $this->context->userCanAccess($user, $module)) {
                $this->context->set($module);
            }

            return $next($request);
        }

        if ($targetModuleCode === null || $targetModuleCode === $activeModule->code) {
            return $next($request);
        }

        $targetName = $this->context->moduleName($targetModuleCode) ?? $targetModuleCode;
        $message = "Anda sedang berada di modul \"{$activeModule->name}\". "
            ."Kembali ke All Modules untuk membuka modul \"{$targetName}\".";

        if ($request->expectsJson()) {
            abort(403, $message);
        }

        return redirect()
            ->route('modules.index')
            ->with('warning', $message);
    }
}
