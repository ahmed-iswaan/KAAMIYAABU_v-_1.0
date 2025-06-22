<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DhiraaguSmsService;  // ← your service, not the Zedox one

class DhiraaguSmsServiceProvider extends ServiceProvider
{
    /**
     * Register the DhiraaguSmsService singleton.
     */
    public function register()
    {
        $this->app->singleton(DhiraaguSmsService::class, function ($app) {
            // The constructor will pull config('services.dhiraagu_sms') automatically
            return new DhiraaguSmsService;
        });
    }

    public function boot()
    {
        //
    }
}
