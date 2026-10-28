<?php

namespace App\Actions\Admin;

use App\Actions\WorkspaceBeneficiaries\SynchronizeIndividualSponsorCoverageAction;
use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\DTOs\Admin\AssignWorkspaceSubscriptionData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Subscriptions\PlanChangeDirection;
use App\Enums\Subscriptions\SubscriptionFundingSource;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Workspace;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Services\Payments\PlanChangeEligibilityService;
use App\Services\Payments\PlanChangePricingService;
use App\Services\Payments\PlanPricingService;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class AssignWorkspaceSubscriptionAction
{
    public function __construct(
        private PlanPricingService $pricing,
        private PlanChangePricingService $changePricing,
        private PlanChangeEligibilityService $eligibility,
        private PaymentReferenceGenerator $references,
        private WorkspaceActivityLogger $activities,
        private SynchronizeIndividualSponsorCoverageAction $synchronizeSponsorCoverage,
    ) {}

    public function execute(User $administrator, AssignWorkspaceSubscriptionData $data): Subscription
    {
        abort_unless($administrator->isAdmin(), 403);

        return DB::transaction(function () use ($administrator, $data): Subscription {
            $workspace = Workspace::query()->whereKey($data->workspaceId)->lockForUpdate()->firstOrFail();
            $plan = Plan::query()->with('features')->whereKey($data->planId)->lockForUpdate()->firstOrFail();

            if (! $plan->is_active || $plan->account_type !== $workspace->type) {
                throw new CheckoutUnavailable('The selected plan is not active for this workspace account type.');
            }

            $subscription = Subscription::query()
                ->with(['plan.features', 'scheduledPlan.features'])
                ->where('subscribable_type', $workspace->getMorphClass())
                ->where('subscribable_id', $workspace->getKey())
                ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
                ->latest('id')
                ->lockForUpdate()
                ->first();

            return $subscription instanceof Subscription
                ? $this->replace($administrator, $workspace, $subscription, $plan, $data)
                : $this->assignInitial($administrator, $workspace, $plan, $data);
        }, 3);
    }

    private function assignInitial(
        User $administrator,
        Workspace $workspace,
        Plan $plan,
        AssignWorkspaceSubscriptionData $data,
    ): Subscription {
        $quote = $this->pricing->checkout($plan, $data->additionalCapacity);
        $startsAt = now();
        $endsAt = $plan->calculateEndsAt($startsAt);

        $subscription = Subscription::query()->create([
            'subscribable_type' => $workspace->getMorphClass(),
            'subscribable_id' => $workspace->getKey(),
            'plan_id' => $plan->getKey(),
            'capacity_count' => $quote->capacityCount ?? 1,
            'status' => SubscriptionStatus::Active,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'auto_renew' => $data->fundingSource === SubscriptionFundingSource::Complimentary
                && $data->complimentaryRenews,
            'next_charge_at' => $data->fundingSource === SubscriptionFundingSource::Complimentary
                && $data->complimentaryRenews ? $endsAt : null,
            'funding_source' => $data->fundingSource,
            'assigned_by_user_id' => $administrator->getKey(),
            'complimentary_renews' => $data->fundingSource === SubscriptionFundingSource::Complimentary
                && $data->complimentaryRenews,
        ]);
        $this->synchronizeSponsorCoverage->execute($workspace, $plan);

        if ($data->fundingSource === SubscriptionFundingSource::Wallet) {
            $this->debitWallet($workspace, $quote->money, $subscription, $plan, $administrator, TransactionTypes::SUBSCRIPTION);
        }

        $this->recordAssignmentActivity($administrator, $workspace, $subscription, $plan, $data);

        return $subscription->load('plan');
    }

    private function replace(
        User $administrator,
        Workspace $workspace,
        Subscription $subscription,
        Plan $plan,
        AssignWorkspaceSubscriptionData $data,
    ): Subscription {
        if ((int) $subscription->plan_id === (int) $plan->getKey()) {
            throw new CheckoutUnavailable('This workspace is already assigned to the selected plan.');
        }

        $direction = $this->changePricing->direction($subscription, $plan);
        $eligibility = $this->eligibility->assess($workspace, $subscription, $plan);

        if (! $eligibility->available()) {
            throw new CheckoutUnavailable(implode(' ', $eligibility->violations));
        }

        $quotedSubscription = clone $subscription;
        $quotedSubscription->setAttribute('capacity_count', $eligibility->targetCapacityCount);
        $quote = $this->changePricing->quote($quotedSubscription, $plan);

        if ($data->fundingSource === SubscriptionFundingSource::Wallet
            && $direction === PlanChangeDirection::UPGRADE) {
            $this->debitWallet(
                $workspace,
                $quote->amountDueNow,
                $subscription,
                $plan,
                $administrator,
                TransactionTypes::PLAN_CHANGE,
            );
        }

        $subscription->update([
            'plan_id' => $plan->getKey(),
            'capacity_count' => $eligibility->targetCapacityCount,
            'scheduled_plan_id' => null,
            'scheduled_plan_change_at' => null,
            'status' => SubscriptionStatus::Active,
            'cancelled_at' => null,
            'funding_source' => $data->fundingSource,
            'assigned_by_user_id' => $administrator->getKey(),
            'complimentary_renews' => $data->fundingSource === SubscriptionFundingSource::Complimentary
                && $data->complimentaryRenews,
            'auto_renew' => $data->fundingSource === SubscriptionFundingSource::Complimentary
                ? $data->complimentaryRenews
                : $subscription->auto_renew,
            'next_charge_at' => $data->fundingSource === SubscriptionFundingSource::Complimentary
                && $data->complimentaryRenews ? $subscription->ends_at : $subscription->next_charge_at,
        ]);
        $this->synchronizeSponsorCoverage->execute($workspace, $plan);

        $this->recordAssignmentActivity($administrator, $workspace, $subscription, $plan, $data);

        return $subscription->refresh()->load('plan');
    }

    private function debitWallet(
        Workspace $workspace,
        Money $amount,
        ?Subscription $subscription,
        Plan $plan,
        User $administrator,
        TransactionTypes $type,
    ): void {
        $wallet = $workspace->wallet()->firstOrCreate([], [
            'balance' => '0.00',
            'currency' => $amount->currency,
        ]);
        $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();

        if ($wallet->currency !== $amount->currency) {
            throw new CheckoutUnavailable('The wallet currency does not match this subscription assignment.');
        }

        $balance = Money::fromMajor($wallet->balance, $wallet->currency);

        if ($balance->amountInMinorUnits < $amount->amountInMinorUnits) {
            throw new CheckoutUnavailable('The workspace wallet balance is insufficient for this subscription assignment.');
        }

        $wallet->update([
            'balance' => (new Money(
                $balance->amountInMinorUnits - $amount->amountInMinorUnits,
                $balance->currency,
            ))->toMajorAmount(),
        ]);

        Transaction::query()->create([
            'payment_id' => null,
            'recorded_by_user_id' => $administrator->getKey(),
            'owner_type' => $workspace->getMorphClass(),
            'owner_id' => $workspace->getKey(),
            'transactable_type' => $subscription?->getMorphClass() ?? $plan->getMorphClass(),
            'transactable_id' => $subscription?->getKey() ?? $plan->getKey(),
            'amount' => $amount->toMajorAmount(),
            'currency' => $amount->currency,
            'reference' => $this->references->generate(
                $type === TransactionTypes::PLAN_CHANGE
                    ? PaymentPurpose::PLAN_UPGRADE
                    : PaymentPurpose::SUBSCRIPTION,
            ),
            'type' => $type,
            'status' => TransactionStatus::COMPLETED,
            'flow' => TransactionFlow::DEBIT,
            'meta' => [
                'description' => 'Administrator-assigned subscription paid from the workspace wallet',
                'payment_source' => SubscriptionFundingSource::Wallet->value,
                'plan_id' => $plan->getKey(),
            ],
        ]);
    }

    private function recordAssignmentActivity(
        User $administrator,
        Workspace $workspace,
        Subscription $subscription,
        Plan $plan,
        AssignWorkspaceSubscriptionData $data,
    ): void {
        $complimentary = $data->fundingSource === SubscriptionFundingSource::Complimentary;

        $this->activities->record($workspace, new WorkspaceActivityData(
            type: $complimentary
                ? WorkspaceActivityType::ComplimentarySubscriptionAssigned
                : WorkspaceActivityType::SubscriptionActivated,
            title: "Assigned {$plan->name}",
            actor: WorkspaceActivityActor::user($administrator),
            subjectType: 'subscription',
            subjectId: $subscription->getKey(),
            subjectName: $plan->name,
            description: $complimentary
                ? 'An administrator assigned complimentary plan access.'
                : 'An administrator assigned the plan using sponsor-funded wallet balance.',
            context: [
                'plan_name' => $plan->name,
                'funding_source' => $data->fundingSource->value,
                'complimentary_renews' => $data->complimentaryRenews,
            ],
        ));
    }
}
