<?php

namespace App\Providers;

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
        View::composer('layouts.navigation', function ($view): void {
            $patient = auth()->user()?->patient;
            $view->with('unreadNotificationCount', $patient?->portalNotifications()->whereNull('read_at')->count() ?? 0);
        });
    }
}
