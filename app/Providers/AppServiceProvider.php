<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Phase 2 - RBAC: Gate `permission` agar Blade @can('bbm.request')
        // dan Policy dapat menggunakan pengecekan berbasis permission.
        Gate::define('permission', function (User $user, string|array $permission, bool $requireAll = false) {
            return $user->hasPermission($permission, $requireAll);
        });

        // Helper: auth()->user()->can('module.action')
        // Sudah ter-cover oleh Gate di atas untuk string tunggal.
    }
}
