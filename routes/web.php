<?php

use App\Http\Controllers\Activity\MarkWorkspaceActivitiesReadController;
use App\Http\Controllers\Activity\WorkspaceActivityIndexController;
use App\Http\Controllers\Appointments\ConsultationController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MedicalAccessRequests\DecideMedicalAccessRequestController;
use App\Http\Controllers\MedicalAccessRequests\MedicalAccessIndexController;
use App\Http\Controllers\MedicalAccessRequests\ShowMedicalAccessRequestReviewController;
use App\Http\Controllers\MedicalAccessRequests\StoreMedicalAccessRequestController;
use App\Http\Controllers\Payments\PaymentCallbackController;
use App\Http\Controllers\Payments\PaymentWebhookController;
use App\Http\Controllers\Payments\StoreCapacityPurchaseController;
use App\Http\Controllers\Payments\StorePlanChangeController;
use App\Http\Controllers\Payments\StorePlanCheckoutController;
use App\Http\Controllers\Payments\StoreWalletPaymentController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WorkspaceBeneficiaries\CancelWorkspaceBeneficiaryInvitationController;
use App\Http\Controllers\WorkspaceBeneficiaries\DecideWorkspaceBeneficiaryInvitationController;
use App\Http\Controllers\WorkspaceBeneficiaries\ImportWorkspaceEmployeesController;
use App\Http\Controllers\WorkspaceBeneficiaries\ResendWorkspaceBeneficiaryInvitationController;
use App\Http\Controllers\WorkspaceBeneficiaries\ShowWorkspaceBeneficiaryInvitationController;
use App\Http\Controllers\WorkspaceBeneficiaries\StoreWorkspaceBeneficiaryController;
use App\Http\Controllers\WorkspaceBeneficiaries\UpdateWorkspaceBeneficiaryAccessController;
use App\Http\Controllers\WorkspaceBeneficiaries\WorkspaceBeneficiaryIndexController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('/callback', PaymentCallbackController::class)
        ->middleware('throttle:60,1')
        ->name('callback');
});

Route::prefix('webhooks')->group(function () {
    Route::post('/payments/{gateway}', PaymentWebhookController::class)
        ->name('payments.webhook');
});

Route::prefix('invitations')->name('workspace-beneficiary-invitations.')->group(function () {
    Route::get('/{workspaceBeneficiary:public_id}', ShowWorkspaceBeneficiaryInvitationController::class)
        ->middleware('throttle:60,1')
        ->name('show');
    Route::post('/{workspaceBeneficiary:public_id}/decision', DecideWorkspaceBeneficiaryInvitationController::class)
        ->middleware('throttle:20,1')
        ->name('decide');
});

Route::prefix('medical-access-reviews')->name('medical-access-reviews.')->group(function () {
    Route::get('/{medicalAccessRequest:public_id}', ShowMedicalAccessRequestReviewController::class)
        ->middleware(['signed', 'throttle:60,1'])
        ->name('show');
    Route::post('/{medicalAccessRequest:public_id}/decision', DecideMedicalAccessRequestController::class)
        ->middleware(['signed', 'throttle:20,1'])
        ->name('decide');
});

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('home');

    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::post('/payments', [StoreWalletPaymentController::class, 'store'])->name('payments.store');
    });

    Route::inertia('/getting-started', 'sponsor/empty-state')->name('sponsor.getting_started');
    Route::get('/beneficiaries', WorkspaceBeneficiaryIndexController::class)->name('beneficiaries.index');

    Route::prefix('workspace-beneficiaries')->name('workspace-beneficiaries.')->group(function () {
        Route::post('/', StoreWorkspaceBeneficiaryController::class)
            ->middleware('throttle:20,1')
            ->name('store');
        Route::post('/imports', ImportWorkspaceEmployeesController::class)
            ->middleware('throttle:5,1')
            ->name('imports.store');
        Route::post('/{workspaceBeneficiary:public_id}/resend', ResendWorkspaceBeneficiaryInvitationController::class)
            ->middleware('throttle:10,1')
            ->name('resend');
        Route::delete('/{workspaceBeneficiary:public_id}', CancelWorkspaceBeneficiaryInvitationController::class)
            ->middleware('throttle:20,1')
            ->name('cancel');
        Route::patch('/{workspaceBeneficiary:public_id}/access', UpdateWorkspaceBeneficiaryAccessController::class)
            ->middleware('throttle:20,1')
            ->name('access.update');
    });

    Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
    Route::get('/medical-access', MedicalAccessIndexController::class)->name('medical_access.index');
    Route::post('/medical-access-requests', StoreMedicalAccessRequestController::class)
        ->middleware('throttle:20,1')
        ->name('medical_access_requests.store');
    Route::get('/billing', BillingController::class)->name('plans.index');

    Route::prefix('plans')->name('plans.')->group(function () {
        Route::post('/{plan:slug}/checkout', [StorePlanCheckoutController::class, 'store'])->name('checkout.store');
    });

    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::post('/{subscription}/capacity-purchases', [StoreCapacityPurchaseController::class, 'store'])
            ->name('capacity_purchases.store');
        Route::post('/{subscription}/plan-changes/{plan:slug}', [StorePlanChangeController::class, 'store'])
            ->name('plan_changes.store')
            ->withoutScopedBindings();
    });

    Route::inertia('/account-settings', 'sponsor/account-settings/index')->name('account_settings.index');
    Route::get('/activity-log', WorkspaceActivityIndexController::class)->name('activity_log.index');
    Route::post('/activity-log/read-all', MarkWorkspaceActivitiesReadController::class)
        ->middleware('throttle:30,1')
        ->name('activity_log.read_all');

    Route::name('business.')->group(function () {
        Route::inertia('/reports', 'business-sponsor/consultations/index')->name('reports');
        Route::get('/employees', WorkspaceBeneficiaryIndexController::class)->name('employees');
        Route::get('/plan-and-seats', BillingController::class)->name('plans');
    });

    Route::prefix('institutional-sponsor')->name('institutional.')->group(function () {
        Route::inertia('/dashboard', 'institutional-sponsor/dashboard')->name('dashboard');
        Route::inertia('/consultations', 'institutional-sponsor/consultations/index')->name('consultations');
        Route::inertia('/notifications', 'institutional-sponsor/notifications/index')->name('notifications');
        Route::inertia('/coverage', 'institutional-sponsor/coverage/index')->name('coverage');
        Route::inertia('/enrollment-codes', 'institutional-sponsor/enrollment-codes/index')->name('enrollment_codes');
        Route::inertia('/reports', 'institutional-sponsor/reports/index')->name('reports');
        Route::inertia('/team', 'institutional-sponsor/team/index')->name('team');
    });
});
