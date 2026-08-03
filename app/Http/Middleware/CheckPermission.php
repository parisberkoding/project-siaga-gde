<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 2 - Permission Middleware
 *
 * Menggantikan pengecekan role langsung dengan pengecekan permission granular.
 * Pemakaian: ->middleware('permission:bbm.request,spd.view')
 *           ->middleware('permission:bbm.approve')  // single
 *
 * Jika lebih dari satu argumen dan butuh semua match, pakai prefix "all:":
 * ->middleware('permission:all:bbm.create,bbm.approve')
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!Auth::check()) {
            abort(403, 'Anda harus login terlebih dahulu.');
        }

        $user = Auth::user();

        $requireAll = false;
        if (isset($permissions[0]) && str_starts_with($permissions[0], 'all:')) {
            $requireAll = true;
            $permissions[0] = substr($permissions[0], 4);
        }

        if (!$user->hasPermission($permissions, $requireAll)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki permission yang diperlukan.');
        }

        return $next($request);
    }
}
