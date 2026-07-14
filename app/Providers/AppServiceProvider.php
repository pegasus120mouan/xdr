<?php

namespace App\Providers;

use App\Models\DetectionRule;
use Illuminate\Pagination\Paginator;
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

            $view->with('containmentEnforced', $blockingEnabled);
        });
    }
}
