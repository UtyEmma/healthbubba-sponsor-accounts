<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Payments\PaymentCallbackController;
use App\Http\Controllers\Payments\PaymentWebhookController;
use App\Http\Controllers\Payments\StoreCapacityPurchaseController;
use App\Http\Controllers\Payments\StorePlanCheckoutController;
use App\Http\Controllers\Payments\StoreWalletPaymentController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/payments/callback', PaymentCallbackController::class)
    ->middleware('throttle:60,1')
    ->name('payments.callback');

Route::post('/webhooks/payments/{gateway}', PaymentWebhookController::class)
    ->name('payments.webhook');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('home');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/payments', [StoreWalletPaymentController::class, 'store'])
        ->name('wallet.payments.store');
    Route::inertia('/getting-started', 'sponsor/empty-state')->name('sponsor.getting_started');
    Route::inertia('/beneficiaries', 'sponsor/beneficiaries/index')->name('beneficiaries.index');
    Route::inertia('/consultations', 'sponsor/consultations/index')->name('consultations.index');
    Route::inertia('/medical-access', 'sponsor/medical-access/index')->name('medical_access.index');
    Route::get('/plan-and-billing', BillingController::class)->name('plans.index');
    Route::post('/plans/{plan:slug}/checkout', [StorePlanCheckoutController::class, 'store'])
        ->name('plans.checkout.store');
    Route::post('/subscriptions/{subscription}/capacity-purchases', [StoreCapacityPurchaseController::class, 'store'])
        ->name('subscriptions.capacity_purchases.store');
    Route::inertia('/account-settings', 'sponsor/account-settings/index')->name('account_settings.index');
    Route::inertia('/activity-log', 'sponsor/activity-log/index')->name('activity_log.index');

    Route::inertia('/business-sponsor/dashboard', 'business-sponsor/dashboard')->name('business.dashboard');
    Route::inertia('/business-sponsor/reports', 'business-sponsor/consultations/index')->name('business.reports');
    Route::inertia('/business-sponsor/employees', 'business-sponsor/employees/index')->name('business.employees');
    Route::get('/business-sponsor/plan-and-seats', BillingController::class)->name('business.plans');

    Route::inertia('/institutional-sponsor/dashboard', 'institutional-sponsor/dashboard')->name('institutional.dashboard');
    Route::inertia('/institutional-sponsor/consultations', 'institutional-sponsor/consultations/index')->name('institutional.consultations');
    Route::inertia('/institutional-sponsor/notifications', 'institutional-sponsor/notifications/index')->name('institutional.notifications');
    Route::inertia('/institutional-sponsor/coverage', 'institutional-sponsor/coverage/index')->name('institutional.coverage');
    Route::inertia('/institutional-sponsor/enrollment-codes', 'institutional-sponsor/enrollment-codes/index')->name('institutional.enrollment_codes');
    Route::inertia('/institutional-sponsor/reports', 'institutional-sponsor/reports/index')->name('institutional.reports');
    Route::inertia('/institutional-sponsor/team', 'institutional-sponsor/team/index')->name('institutional.team');
});
