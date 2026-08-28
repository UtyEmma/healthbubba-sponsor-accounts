<?php

use App\Providers\AccountVerificationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\PaymentServiceProvider;

return [
    AppServiceProvider::class,
    AccountVerificationServiceProvider::class,
    PaymentServiceProvider::class,
    AdminPanelProvider::class,
    FortifyServiceProvider::class,
];
