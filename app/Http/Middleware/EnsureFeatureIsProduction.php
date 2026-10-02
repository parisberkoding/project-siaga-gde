<?php

namespace App\Http\Middleware;

use App\Models\Feature;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tolak akses ke fitur yang masih development, kecuali bagi pemegang
 * permission features.manage (role developers).
 *
 * Pemakaian:
 *   ->middleware('feature:spd.lpj')            // satu fitur eksplisit
 *   ->middleware('feature:spd.lpj,lunch.dashboard')
 *   ->middleware('feature')                    // tanpa argumen -> pakai nama route
 *
 * Fitur yang TIDAK terdaftar dianggap production (fail-open), sehingga route
 * yang belum didaftarkan tidak pernah terblokir secara tidak sengaja.
 */
class EnsureFeatureIsProduction
{
    public function handle(Request $request, Closure $next, string ...$featureCodes): Response
    {
        if (! Auth::check()) {
            abort(403, 'Anda harus login terlebih dahulu.');
        }

        // Tanpa argumen eksplisit, gunakan nama route sebagai kode fitur.
        // Ini ergonomis untuk route yang nama route-nya sudah didaftarkan di
        // tabel features.
        if ($featureCodes === []) {
            $routeName = $request->route()?->getName();

            if ($routeName) {
                $featureCodes = [$routeName];
            }
        }

        $user = Auth::user();

        foreach ($featureCodes as $code) {
            if (Feature::isRestricted($code) && ! $user->hasPermission('features.manage')) {
                abort(403, 'Halaman ini masih dalam tahap pengembangan dan belum tersedia untuk Anda.');
            }
        }

        return $next($request);
    }
}
