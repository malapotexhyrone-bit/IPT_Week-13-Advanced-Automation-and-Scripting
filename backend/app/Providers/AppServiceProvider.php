<?php

namespace App\Providers;

use App\Contracts\NotificationGateway;
use App\Services\LocalAuditNotificationGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(NotificationGateway::class, LocalAuditNotificationGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
