<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    private const LEGACY_TO_RBAC = [
        'superadmin'   => 'superadmin',
        'admin'        => 'general_affairs',
        'admin_bbm'    => 'general_affairs',
        'dispatcher'   => 'dispatcher',
        'requester'    => 'requester',
        'driver'       => 'requester',
        'manager'      => 'manager',
        'non_ga_staff' => 'view_only',
    ];

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            abort(403, 'Anda harus login terlebih dahulu.');
        }

        $user = Auth::user();

        if (!$this->hasRole($user, $roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }

    protected function hasRole($user, array $roles): bool
    {
        // Cek legacy role column
        if (in_array($user->role, $roles)) {
            return true;
        }

        // Cek legacy role via map
        foreach ($roles as $role) {
            $mapped = self::LEGACY_TO_RBAC[$role] ?? null;
            if ($mapped && $mapped === $user->role) {
                return true;
            }
        }

        // Cek RBAC roles (user_roles pivot)
        $user->loadMissing('roles');
        foreach ($user->roles as $userRole) {
            if (in_array($userRole->code, $roles)) {
                return true;
            }
            foreach ($roles as $role) {
                $mapped = self::LEGACY_TO_RBAC[$role] ?? null;
                if ($mapped && $mapped === $userRole->code) {
                    return true;
                }
            }
        }

        return false;
    }
}
