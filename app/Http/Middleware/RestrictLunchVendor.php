<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role vendor hanya boleh membuka daftar kehadiran harian.
 *
 * Permission `lunch.view` juga diberikan ke vendor, sehingga tanpa guard ini
 * vendor bisa membuka dashboard/forecast/distribusi dan melihat data seluruh
 * unit (terutama bila unit user masih kosong = global).
 */
class RestrictLunchVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRoleCode('vendor') && ! $user->hasPermission('lunch.manage')) {
            abort(403, 'Vendor hanya dapat mengakses daftar kehadiran harian.');
        }

        return $next($request);
    }
}
