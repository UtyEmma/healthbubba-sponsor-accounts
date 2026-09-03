<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:dispatch-renewals')
    ->everyFifteenMinutes()
    ->withoutOverlapping(14)
    ->onOneServer();

Schedule::command('workspace-beneficiaries:expire-invitations')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('medical-access:expire')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('workspace-members:expire-invitations')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('campaigns:process-lifecycle')
    ->dailyAt('00:10')
    ->withoutOverlapping()
    ->onOneServer();
