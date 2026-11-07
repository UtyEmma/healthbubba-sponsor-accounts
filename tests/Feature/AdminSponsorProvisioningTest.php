<?php

use App\Actions\Admin\AssignWorkspaceSubscriptionAction;
use App\Actions\Admin\ProvisionSponsorAccountAction;
use App\Actions\Admin\RecordAdminWorkspaceFundingAction;
use App\Actions\Workspaces\CreateNewWorkspace;
use App\DTOs\Admin\AssignWorkspaceSubscriptionData;
use App\DTOs\Admin\ProvisionSponsorAccountData;
use App\DTOs\Admin\RecordAdminWorkspaceFundingData;
use App\DTOs\Workspaces\CreateWorkspaceData;
use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\AccountTypes;
use App\Enums\Subscriptions\SubscriptionFundingSource;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionTypes;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\AdminAccountAccessInvitation;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

it('provisions a new sponsor identity and owned workspace through the domain action', function () {
    Notification::fake();
    $administrator = User::factory()->create(['role' => Roles::ADMIN]);

    $workspace = app(ProvisionSponsorAccountAction::class)->execute(
        $administrator,
        new ProvisionSponsorAccountData(
            existingUserId: null,
            name: 'Ada Sponsor',
            email: ' ADA@EXAMPLE.COM ',
            phone: '+2348000000000',
            status: Status::ACTIVE,
            role: Roles::USER,
            password: null,
            emailVerified: true,
            phoneVerified: false,
            accountVerified: true,
            sendInvitation: false,
            workspace: new CreateWorkspaceData(
                name: 'Ada Ventures',
                accountType: AccountTypes::BUSINESS,
                memberPhone: '+2348000000000',
            ),
        ),
    );

    $owner = User::query()->where('email', 'ada@example.com')->firstOrFail();

    expect($workspace->type)->toBe(AccountTypes::BUSINESS)
        ->and($owner->email_verified_at)->not->toBeNull()
        ->and($workspace->members()->where('user_id', $owner->getKey())->where('role', 'owner')->exists())->toBeTrue();

    Notification::assertSentTo($owner, AdminAccountAccessInvitation::class);
});

it('prevents a normal administrator from elevating a newly provisioned identity', function () {
    $administrator = User::factory()->create(['role' => Roles::ADMIN]);

    expect(fn () => app(ProvisionSponsorAccountAction::class)->execute(
        $administrator,
        new ProvisionSponsorAccountData(
            existingUserId: null,
            name: 'Elevated User',
            email: 'elevated@example.com',
            phone: null,
            status: Status::ACTIVE,
            role: Roles::ADMIN,
            password: 'a-secure-password-value',
            emailVerified: false,
            phoneVerified: false,
            accountVerified: false,
            sendInvitation: false,
            workspace: new CreateWorkspaceData('Elevated Workspace', AccountTypes::BUSINESS),
        ),
    ))->toThrow(ValidationException::class);

    expect(User::query()->where('email', 'elevated@example.com')->exists())->toBeFalse();
});

it('records sponsor-paid admin funding as an auditable wallet credit', function () {
    $administrator = User::factory()->create(['role' => Roles::ADMIN]);
    $owner = User::factory()->create();
    $workspace = app(CreateNewWorkspace::class)->execute(
        $owner,
        new CreateWorkspaceData('Sponsor Business', AccountTypes::BUSINESS),
    );

    $transaction = app(RecordAdminWorkspaceFundingAction::class)->execute(
        $administrator,
        new RecordAdminWorkspaceFundingData(
            workspaceId: $workspace->getKey(),
            amount: Money::fromMajor('250000', 'NGN'),
            note: 'Sponsor bank receipt verified',
            externalReference: 'BANK-123',
        ),
    );

    expect($workspace->wallet()->firstOrFail()->balance)->toBe('250000.00')
        ->and($transaction->type)->toBe(TransactionTypes::TOPUP)
        ->and($transaction->flow)->toBe(TransactionFlow::CREDIT)
        ->and($transaction->recorded_by_user_id)->toBe($administrator->getKey())
        ->and($transaction->meta['funding_source'])->toBe('sponsor')
        ->and($transaction->meta['recording_channel'])->toBe('admin');
});

it('assigns complimentary plans without debiting the sponsor wallet', function () {
    $administrator = User::factory()->create(['role' => Roles::ADMIN]);
    $owner = User::factory()->create();
    $workspace = app(CreateNewWorkspace::class)->execute(
        $owner,
        new CreateWorkspaceData('Sponsor Business', AccountTypes::BUSINESS),
    );
    $plan = Plan::query()->create([
        'name' => 'Business Admin Plan',
        'slug' => 'business-admin-plan',
        'account_type' => AccountTypes::BUSINESS,
        'price' => '10000.00',
        'is_active' => true,
        'is_free' => false,
        'trial_days' => 0,
        'billing_period' => 1,
        'billing_interval' => 'month',
    ]);

    $subscription = app(AssignWorkspaceSubscriptionAction::class)->execute(
        $administrator,
        new AssignWorkspaceSubscriptionData(
            workspaceId: $workspace->getKey(),
            planId: $plan->getKey(),
            fundingSource: SubscriptionFundingSource::Complimentary,
            complimentaryRenews: true,
        ),
    );

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->funding_source)->toBe(SubscriptionFundingSource::Complimentary)
        ->and($subscription->complimentary_renews)->toBeTrue()
        ->and($subscription->auto_renew)->toBeTrue()
        ->and($workspace->wallet()->firstOrFail()->balance)->toBe('0.00')
        ->and(Transaction::query()->where('type', TransactionTypes::SUBSCRIPTION)->exists())->toBeFalse();
});

it('creates a wallet-funded subscription and records the administrator debit', function () {
    $administrator = User::factory()->create(['role' => Roles::ADMIN]);
    $owner = User::factory()->create();
    $workspace = app(CreateNewWorkspace::class)->execute(
        $owner,
        new CreateWorkspaceData('Funded Sponsor Business', AccountTypes::BUSINESS),
    );
    $workspace->wallet()->update(['balance' => '250000.00']);
    $plan = Plan::query()->create([
        'name' => 'Business Wallet Plan',
        'slug' => 'business-wallet-plan',
        'account_type' => AccountTypes::BUSINESS,
        'price' => '10000.00',
        'is_active' => true,
        'is_free' => false,
        'trial_days' => 0,
        'billing_period' => 1,
        'billing_interval' => 'month',
    ]);

    $subscription = app(AssignWorkspaceSubscriptionAction::class)->execute(
        $administrator,
        new AssignWorkspaceSubscriptionData(
            workspaceId: $workspace->getKey(),
            planId: $plan->getKey(),
            fundingSource: SubscriptionFundingSource::Wallet,
        ),
    );

    $transaction = Transaction::query()
        ->where('type', TransactionTypes::SUBSCRIPTION)
        ->firstOrFail();

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->funding_source)->toBe(SubscriptionFundingSource::Wallet)
        ->and($subscription->assigned_by_user_id)->toBe($administrator->getKey())
        ->and($workspace->wallet()->firstOrFail()->balance)->toBe('240000.00')
        ->and($transaction->flow)->toBe(TransactionFlow::DEBIT)
        ->and($transaction->amount)->toBe('10000.00')
        ->and($transaction->recorded_by_user_id)->toBe($administrator->getKey());
});
