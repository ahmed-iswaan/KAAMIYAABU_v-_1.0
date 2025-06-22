<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Services\TelegramService;
use App\Services\DhiraaguSmsService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TelegramService::class, function ($app) {
            return new TelegramService();
        });
        $this->app->singleton(DhiraaguSmsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
            Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('azure', \SocialiteProviders\Azure\Provider::class);
        });

        Event::listen(Authenticated::class, UpdateLastLoginAt::class);
    }
}
