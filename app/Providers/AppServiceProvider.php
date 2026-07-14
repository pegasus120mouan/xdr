<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\DetectionRule;
use App\Models\SecurityAlert;
use App\Support\TenantContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.wara');
        Paginator::defaultSimpleView('vendor.pagination.simple-wara');

        View::composer('layouts.app', function ($view) {
            $blockingEnabled = DetectionRule::query()
                ->where('category', 'brute_force')
                ->where('is_active', true)
                ->get()
                ->contains(
                    fn (DetectionRule $rule) => collect($rule->actions ?? [])->contains(
                        fn (array $a) => ($a['type'] ?? '') === 'block_ip'
                    )
                );

            $statusBar = [
                'total' => 0,
                'online' => 0,
                'offline' => 0,
                'alerting' => 0,
                'open_alerts' => 0,
            ];

            if (Auth::check()) {
                $user = Auth::user();
                $assets = Asset::query();
                TenantContext::scopeAssets($assets, $user);
                $alerts = SecurityAlert::query();
                TenantContext::scopeAlerts($alerts, $user);

                $statusBar = [
                    'total' => (clone $assets)->count(),
                    'online' => (clone $assets)->where('status', 'online')->count(),
                    'offline' => (clone $assets)->where('status', 'offline')->count(),
                    'alerting' => (clone $assets)->where('status', 'alerting')->count(),
                    'open_alerts' => (clone $alerts)->whereIn('status', ['new', 'investigating', 'escalated'])->count(),
                ];
            }

            $view->with([
                'containmentEnforced' => $blockingEnabled,
                'statusBar' => $statusBar,
            ]);
        });
    }
}
