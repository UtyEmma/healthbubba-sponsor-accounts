<?php

namespace App\Actions\Payments;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\Payments\SubscriptionPaymentSource;
use App\Enums\Payments\WalletRenewalResult;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Workspace;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Services\Payments\PlanPricingService;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class AttemptWalletSubscriptionRenewalAction
{
    public function __construct(
        private PlanPricingService $pricing,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(int $subscriptionId, bool $allowPastDue = false): WalletRenewalResult
    {
        return DB::transaction(function () use ($subscriptionId, $allowPastDue): WalletRenewalResult {
            $subscription = Subscription::query()
                ->with(['plan.features', 'scheduledPlan.features'])
                ->whereKey($subscriptionId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->isPayable($subscription, $allowPastDue)) {
                return WalletRenewalResult::NOT_DUE;
            }

            $workspace = Workspace::query()
                ->whereKey($subscription->subscribable_id)
                ->lockForUpdate()
                ->firstOrFail();
            $renewalPlan = $this->renewalPlan($subscription);
            $quote = $this->pricing->renewalForPlan($subscription, $renewalPlan);
            $wallet = $workspace->wallet()->firstOrCreate([], [
                'balance' => '0.00',
                'currency' => $quote->money->currency,
            ]);
            $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();

            if ($wallet->currency !== $quote->money->currency) {
                throw new CheckoutUnavailable('The wallet currency does not match this renewal.');
            }

            $balance = Money::fromMajor($wallet->balance, $wallet->currency);

            if ($balance->amountInMinorUnits < $quote->money->amountInMinorUnits) {
                return WalletRenewalResult::INSUFFICIENT;
            }

            $dueAt = $subscription->next_charge_at ?? $subscription->ends_at ?? now();
            $reference = sprintf(
                'SUB-WAL-%d-%s',
                $subscription->getKey(),
                mb_strtoupper(mb_substr(hash('sha256', $dueAt->toISOString()), 0, 20)),
            );

            if (Transaction::query()->where('reference', $reference)->exists()) {
                return WalletRenewalResult::PAID;
            }

            $now = now();
            $renewalBase = $subscription->ends_at?->isFuture() === true
                ? $subscription->ends_at
                : $now;
            $endsAt = $renewalPlan->calculateEndsAt($renewalBase);

            $wallet->update([
                'balance' => (new Money(
                    $balance->amountInMinorUnits - $quote->money->amountInMinorUnits,
                    $balance->currency,
                ))->toMajorAmount(),
            ]);
            $subscription->update([
                'plan_id' => $renewalPlan->getKey(),
                'scheduled_plan_id' => null,
                'scheduled_plan_change_at' => null,
                'capacity_count' => $quote->capacityCount,
                'status' => SubscriptionStatus::Active,
                'starts_at' => $now,
                'ends_at' => $endsAt,
                'renewed_at' => $now,
                'cancelled_at' => null,
                'auto_renew' => true,
                'next_charge_at' => $endsAt,
                'renewal_attempts' => 0,
                'renewal_retry_at' => null,
            ]);

            Transaction::query()->create([
                'payment_id' => null,
                'owner_type' => $workspace->getMorphClass(),
                'owner_id' => $workspace->getKey(),
                'transactable_type' => $subscription->getMorphClass(),
                'transactable_id' => $subscription->getKey(),
                'amount' => $quote->money->toMajorAmount(),
                'currency' => $quote->money->currency,
                'reference' => $reference,
                'type' => TransactionTypes::SUBSCRIPTION,
                'status' => TransactionStatus::COMPLETED,
                'flow' => TransactionFlow::DEBIT,
                'meta' => [
                    'description' => 'Plan subscription renewal',
                    'payment_source' => SubscriptionPaymentSource::WALLET->value,
                    'plan_id' => $renewalPlan->getKey(),
                    'renewal_due_at' => $dueAt->toISOString(),
                ],
            ]);

            $this->activities->record($workspace, new WorkspaceActivityData(
                type: WorkspaceActivityType::SubscriptionRenewed,
                title: "Renewed {$renewalPlan->name}",
                actor: WorkspaceActivityActor::system(),
                subjectType: 'subscription',
                subjectId: $subscription->getKey(),
                subjectName: $renewalPlan->name,
                description: 'The renewal was paid from the workspace wallet.',
                context: [
                    'plan_name' => $renewalPlan->name,
                    'amount_minor' => $quote->money->amountInMinorUnits,
                    'currency' => $quote->money->currency,
                ],
            ));

            return WalletRenewalResult::PAID;
        }, 3);
    }

    private function renewalPlan(Subscription $subscription): Plan
    {
        if ($subscription->scheduledPlan instanceof Plan
            && $subscription->scheduled_plan_change_at?->isPast() === true) {
            return $subscription->scheduledPlan;
        }

        return $subscription->plan;
    }

    private function isPayable(Subscription $subscription, bool $allowPastDue): bool
    {
        if ($subscription->subscribable_type !== (new Workspace)->getMorphClass()) {
            return false;
        }

        if ($allowPastDue && $subscription->status === SubscriptionStatus::PastDue) {
            return true;
        }

        if (! $subscription->auto_renew || $subscription->status !== SubscriptionStatus::Active) {
            return false;
        }

        if ($subscription->renewal_retry_at !== null) {
            return $subscription->renewal_retry_at->isPast();
        }

        return $subscription->next_charge_at?->isPast() === true;
    }
}
