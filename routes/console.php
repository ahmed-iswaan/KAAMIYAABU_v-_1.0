<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('telegram:send-pending')
    ->everyTenSeconds();

Schedule::command('invoices:update-fines')
             ->everyTenSeconds()
             ->withoutOverlapping()
             ->description('Recalculate invoice fines every hour');

Schedule::command('invoices:generate-scheduled')->everyTenSeconds();

Schedule::command('waste:generate-tasks')->everyTenSeconds();
