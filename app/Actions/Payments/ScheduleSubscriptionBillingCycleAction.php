<?php

namespace App\Actions\Payments;

use App\DTOs\Payments\ScheduleSubscriptionBillingCycleData;
use App\Enums\AccountTypes;
use App\Enums\CapacityPurchases\CapacityPurchaseStatus;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Subscriptions\IndividualPlanBillingCycle;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\CapacityPurchase;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Payments\IndividualPlanIntervalPricingService;
use Illuminate\Support\Facades\DB;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class ScheduleSubscriptionBillingCycleAction
{
    public function __construct(
        private IndividualPlanIntervalPricingService $pricing,
    ) {}

    public function execute(ScheduleSubscriptionBillingCycleData $data): Subscription
    {
        return DB::transaction(function () use ($data): Subscription {
            $subscription = Subscription::query()
                ->with('plan')
                ->whereKey($data->subscription->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($data->workspace->type !== AccountTypes::INDIVIDUAL
                || $subscription->subscribable_type !== $data->workspace->getMorphClass()
                || (int) $subscription->subscribable_id !== (int) $data->workspace->getKey()
                || ! in_array($subscription->status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true)) {
                throw new CheckoutUnavailable('Only an active Individual subscription can change billing interval.');
            }

            $this->ensureNoPendingPayments($subscription);
            $this->pricing->price($subscription->plan, $data->billingCycle);

            $current = $subscription->billing_cycle ?? IndividualPlanBillingCycle::MONTHLY;
            $subscription->update([
                'scheduled_billing_cycle' => $data->billingCycle === $current
                    ? null
                    : $data->billingCycle->value,
            ]);

            return $subscription->refresh();
        }, 3);
    }

    private function ensureNoPendingPayments(Subscription $subscription): void
    {
        $hasPayment = Payment::query()
            ->where('payable_type', $subscription->getMorphClass())
            ->where('payable_id', $subscription->getKey())
            ->whereIn('purpose', [PaymentPurpose::SUBSCRIPTION, PaymentPurpose::PLAN_UPGRADE])
            ->whereIn('status', [
                PaymentStatus::PENDING,
                PaymentStatus::PROCESSING,
                PaymentStatus::REQUIRES_REVIEW,
            ])
            ->exists();
        $hasCapacityPurchase = CapacityPurchase::query()
            ->whereBelongsTo($subscription)
            ->whereIn('status', [
                CapacityPurchaseStatus::PENDING,
                CapacityPurchaseStatus::REQUIRES_REVIEW,
            ])
            ->exists();

        if ($hasPayment || $hasCapacityPurchase) {
            throw new CheckoutUnavailable('Complete the pending subscription operation before changing billing interval.');
        }
    }
}
