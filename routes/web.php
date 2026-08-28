<?php

use App\Enums\AccountTypes;
use App\Http\Controllers\AccountSettings\UpdateWorkspaceDetailsController;
use App\Http\Controllers\Activity\MarkWorkspaceActivitiesReadController;
use App\Http\Controllers\Activity\WorkspaceActivityIndexController;
use App\Http\Controllers\Appointments\ConsultationController;
use App\Http\Controllers\Appointments\UpdateAllocationFallbackController;
use App\Http\Controllers\Auth\SendAccountVerificationCodeController;
use App\Http\Controllers\Auth\ShowAccountVerificationCompletedController;
use App\Http\Controllers\Auth\ShowAccountVerificationController;
use App\Http\Controllers\Auth\StoreInstitutionalSponsorRegistrationController;
use App\Http\Controllers\Auth\UpdatePendingAccountContactController;
use App\Http\Controllers\Auth\VerifyAccountController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstitutionalCampaigns\ImportCampaignBeneficiariesController;
use App\Http\Controllers\InstitutionalCampaigns\PurchaseCampaignConsultationQuotaController;
use App\Http\Controllers\InstitutionalCampaigns\StoreCampaignBeneficiaryController;
use App\Http\Controllers\InstitutionalCampaigns\StoreInstitutionalCampaignController;
use App\Http\Controllers\InstitutionalCampaigns\UpdateCampaignBeneficiaryAccessController;
use App\Http\Controllers\InstitutionalOnboarding\CompleteInstitutionalOrganizationProfileController;
use App\Http\Controllers\InstitutionalOnboarding\ShowInstitutionalOrganizationController;
use App\Http\Controllers\InstitutionalOnboarding\ShowInstitutionalSupportController;
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
use App\Http\Controllers\Reports\BusinessConsultationReportController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WorkspaceBeneficiaries\CancelWorkspaceBeneficiaryInvitationController;
use App\Http\Controllers\WorkspaceBeneficiaries\DecideWorkspaceBeneficiaryInvitationController;
use App\Http\Controllers\WorkspaceBeneficiaries\ImportWorkspaceEmployeesController;
use App\Http\Controllers\WorkspaceBeneficiaries\ResendWorkspaceBeneficiaryInvitationController;
use App\Http\Controllers\WorkspaceBeneficiaries\ShowWorkspaceBeneficiaryInvitationController;
use App\Http\Controllers\WorkspaceBeneficiaries\StoreWorkspaceBeneficiaryController;
use App\Http\Controllers\WorkspaceBeneficiaries\UpdateWorkspaceBeneficiaryAccessController;
use App\Http\Controllers\WorkspaceBeneficiaries\WorkspaceBeneficiaryIndexController;
use App\Http\Controllers\WorkspaceMembers\AcceptWorkspaceMemberInvitationController;
use App\Http\Controllers\WorkspaceMembers\CancelWorkspaceMemberInvitationController;
use App\Http\Controllers\WorkspaceMembers\DeclineWorkspaceMemberInvitationController;
use App\Http\Controllers\WorkspaceMembers\ResendWorkspaceMemberInvitationController;
use App\Http\Controllers\WorkspaceMembers\SelectWorkspaceController;
use App\Http\Controllers\WorkspaceMembers\ShowWorkspaceMemberInvitationController;
use App\Http\Controllers\WorkspaceMembers\StoreWorkspaceMemberInvitationController;
use App\Http\Controllers\WorkspaceMembers\UpdateWorkspaceMemberAccessController as UpdateTeamMemberAccessController;
use App\Http\Controllers\WorkspaceMembers\UpdateWorkspaceMemberRoleController;
use App\Http\Controllers\WorkspaceMembers\WorkspaceTeamIndexController;
use App\Http\Middleware\EnsureInstitutionalAccountVerified;
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

Route::prefix('team-invitations')->name('team-invitations.')->group(function () {
    Route::get('/{workspaceMember:public_id}', ShowWorkspaceMemberInvitationController::class)
        ->middleware('throttle:60,1')
        ->name('show');
    Route::post('/{workspaceMember:public_id}/accept', AcceptWorkspaceMemberInvitationController::class)
        ->middleware(['signed', 'throttle:20,1'])
        ->name('accept');
    Route::post('/{workspaceMember:public_id}/decline', DeclineWorkspaceMemberInvitationController::class)
        ->middleware(['signed', 'throttle:20,1'])
        ->name('decline');
});

Route::post('/register/institutional', StoreInstitutionalSponsorRegistrationController::class)
    ->middleware(['guest', 'throttle:10,1'])
    ->name('institutional_registration.store');

Route::middleware('auth')
    ->prefix('account-verification')
    ->name('account_verification.')
    ->group(function (): void {
        Route::get('/', ShowAccountVerificationController::class)->name('show');
        Route::post('/code', SendAccountVerificationCodeController::class)
            ->middleware('throttle:5,1')
            ->name('send');
        Route::post('/verify', VerifyAccountController::class)
            ->middleware('throttle:10,1')
            ->name('verify');
        Route::patch('/contact', UpdatePendingAccountContactController::class)
            ->middleware('throttle:5,1')
            ->name('contact.update');
        Route::get('/completed', ShowAccountVerificationCompletedController::class)
            ->name('completed');
    });

$individualWorkspace = 'workspace.type:'.AccountTypes::INDIVIDUAL->value;
$businessWorkspace = 'workspace.type:'.AccountTypes::BUSINESS->value;
$institutionalWorkspace = 'workspace.type:'.AccountTypes::INSTITUTION->value;
$subscriptionWorkspace = $individualWorkspace.','.AccountTypes::BUSINESS->value;

Route::middleware(['auth', EnsureInstitutionalAccountVerified::class])->group(function () use (
    $businessWorkspace,
    $individualWorkspace,
    $institutionalWorkspace,
    $subscriptionWorkspace,
): void {
    Route::middleware($institutionalWorkspace)
        ->prefix('institutional-onboarding')
        ->name('institutional_onboarding.')
        ->group(function (): void {
            Route::get('/organization', ShowInstitutionalOrganizationController::class)
                ->name('organization.edit');
            Route::post('/organization', CompleteInstitutionalOrganizationProfileController::class)
                ->middleware('throttle:10,1')
                ->name('organization.update');
            Route::get('/contact-support', ShowInstitutionalSupportController::class)
                ->name('support');
        });

    Route::get('/', DashboardController::class)->name('home');

    Route::prefix('wallet')->name('wallet.')->group(function (): void {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::post('/payments', [StoreWalletPaymentController::class, 'store'])->name('payments.store');
    });

    Route::middleware($individualWorkspace)->group(function (): void {
        Route::inertia('/getting-started', 'sponsor/empty-state')->name('sponsor.getting_started');
        Route::get('/beneficiaries', WorkspaceBeneficiaryIndexController::class)->name('beneficiaries.index');
        Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
        Route::get('/medical-access', MedicalAccessIndexController::class)->name('medical_access.index');
        Route::post('/medical-access-requests', StoreMedicalAccessRequestController::class)
            ->middleware('throttle:20,1')
            ->name('medical_access_requests.store');
    });

    Route::middleware($subscriptionWorkspace)->group(function (): void {
        Route::prefix('workspace-beneficiaries')->name('workspace-beneficiaries.')->group(function (): void {
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

        Route::patch('/consultations/allocation-fallback', UpdateAllocationFallbackController::class)
            ->middleware('throttle:30,1')
            ->name('consultations.allocation_fallback.update');
        Route::get('/billing', BillingController::class)->name('plans.index');
        Route::post('/plans/{plan:slug}/checkout', [StorePlanCheckoutController::class, 'store'])
            ->name('plans.checkout.store');
        Route::post('/subscriptions/{subscription}/capacity-purchases', [StoreCapacityPurchaseController::class, 'store'])
            ->name('subscriptions.capacity_purchases.store');
        Route::post('/subscriptions/{subscription}/plan-changes/{plan:slug}', [StorePlanChangeController::class, 'store'])
            ->name('subscriptions.plan_changes.store')
            ->withoutScopedBindings();
        Route::get('/activity-log', WorkspaceActivityIndexController::class)->name('activity_log.index');
        Route::post('/activity-log/read-all', MarkWorkspaceActivitiesReadController::class)
            ->middleware('throttle:30,1')
            ->name('activity_log.read_all');
    });

    Route::prefix('account-settings')->name('account_settings.')->group(function (): void {
        Route::inertia('/', 'account-settings/index')->name('index');
        Route::patch('/workspace', UpdateWorkspaceDetailsController::class)
            ->middleware('throttle:20,1')
            ->name('workspace.update');
    });

    Route::get('/team', WorkspaceTeamIndexController::class)->name('team.index');
    Route::prefix('team')->name('team.')->group(function (): void {
        Route::post('/invitations', StoreWorkspaceMemberInvitationController::class)
            ->middleware('throttle:20,1')->name('invitations.store');
        Route::post('/invitations/{workspaceMember:public_id}/resend', ResendWorkspaceMemberInvitationController::class)
            ->middleware('throttle:10,1')->name('invitations.resend');
        Route::delete('/invitations/{workspaceMember:public_id}', CancelWorkspaceMemberInvitationController::class)
            ->middleware('throttle:20,1')->name('invitations.cancel');
        Route::patch('/members/{workspaceMember:public_id}/access', UpdateTeamMemberAccessController::class)
            ->middleware('throttle:30,1')->name('members.access.update');
        Route::patch('/members/{workspaceMember:public_id}/role', UpdateWorkspaceMemberRoleController::class)
            ->middleware('throttle:30,1')->name('members.role.update');
    });

    Route::post('/workspaces/{workspace}/select', SelectWorkspaceController::class)
        ->middleware('throttle:30,1')->name('workspaces.select');

    Route::middleware($businessWorkspace)->name('business.')->group(function (): void {
        Route::get('/business/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/reports', BusinessConsultationReportController::class)->name('reports');
        Route::get('/employees', WorkspaceBeneficiaryIndexController::class)->name('employees');
        Route::get('/plan-and-seats', BillingController::class)->name('plans');
    });

    Route::middleware($institutionalWorkspace)->group(function (): void {
        Route::redirect('/coverage', '/campaigns')->name('institutional.coverage');

        Route::prefix('campaigns')->group(function (): void {
            Route::get('/', [CampaignController::class, 'index'])->name('campaigns.index');
            Route::post('/', StoreInstitutionalCampaignController::class)
                ->middleware('throttle:10,1')
                ->name('campaigns.store');
            Route::get('/{campaign:slug}', [CampaignController::class, 'show'])->name('campaigns.show');
            Route::post('/{campaign:slug}/beneficiaries', StoreCampaignBeneficiaryController::class)
                ->middleware('throttle:20,1')
                ->name('campaigns.beneficiaries.store');
            Route::post('/{campaign:slug}/beneficiaries/imports', ImportCampaignBeneficiariesController::class)
                ->middleware('throttle:5,1')
                ->name('campaigns.beneficiaries.imports.store');
            Route::patch('/{campaign:slug}/beneficiaries/{workspaceBeneficiary:public_id}/access', UpdateCampaignBeneficiaryAccessController::class)
                ->middleware('throttle:20,1')
                ->name('campaigns.beneficiaries.access.update');
            Route::post('/{campaign:slug}/consultation-quotas', PurchaseCampaignConsultationQuotaController::class)
                ->middleware('throttle:10,1')
                ->name('campaigns.consultation-quotas.store');
        });

        Route::prefix('institutional-sponsor')->name('institutional.')->group(function (): void {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');
            Route::redirect('/consultations', '/campaigns')->name('consultations');
            Route::inertia('/notifications', 'institutional-sponsor/notifications/index')->name('notifications');
            Route::redirect('/enrollment-codes', '/campaigns')->name('enrollment_codes');
            Route::inertia('/reports', 'institutional-sponsor/reports/index')->name('reports');
            Route::redirect('/team', '/team')->name('team');
        });
    });
});
