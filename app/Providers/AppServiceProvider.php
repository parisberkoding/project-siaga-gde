<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\User;
use App\Services\Sso\Contracts\SsoProvider;
use App\Services\Sso\Providers\NotConfiguredProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Adapter SSO dipilih lewat konfigurasi, sehingga saat IT Pusat
        // menetapkan protokolnya cukup ubah SSO_DRIVER — tidak ada kode yang
        // perlu disentuh. Default `not_configured` menolak dengan pesan jelas.
        $this->app->bind(SsoProvider::class, function ($app) {
            $driver = (string) config('sso.driver', 'not_configured');
            $class = config("sso.providers.{$driver}") ?: NotConfiguredProvider::class;

            return $app->make($class);
        });
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

        // ── Item 6: gateway feature stage di level view ──
        // Mendukung penggunaan di Blade:
        //   @featureProduction('spd.lpj') ... @elsefeatureProduction ... @endfeatureProduction
        //   @featureDevelopment('ai.assistant') ... @endfeatureDevelopment
        //   @featureVisible('spd.lpj') ... @endfeatureVisible
        Blade::if('featureProduction', fn (string $code) => feature_is_production($code));

        Blade::if('featureDevelopment', fn (string $code) => ! feature_is_production($code));

        Blade::if('featureVisible', fn (string $code) => feature_is_visible($code));

        // Helper: auth()->user()->can('module.action')
        // Sudah ter-cover oleh Gate di atas untuk string tunggal.
    }
}
