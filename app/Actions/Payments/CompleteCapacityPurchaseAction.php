<?php

namespace App\Actions\Payments;

use App\DTOs\CapacityPurchases\CapacityConfiguration;
use App\Enums\CapacityPurchases\CapacityPurchaseStatus;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\CapacityPurchase;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\Payments\CapacityPricingService;
use Illuminate\Support\Facades\DB;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class CompleteCapacityPurchaseAction
{
    public function __construct(
        private CapacityPricingService $pricing,
    ) {}

    public function execute(CapacityPurchase $purchase): CapacityPurchase
    {
        return DB::transaction(function () use ($purchase): CapacityPurchase {
            $lockedPurchase = CapacityPurchase::query()
                ->whereKey($purchase->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPurchase->status === CapacityPurchaseStatus::COMPLETED) {
                return $lockedPurchase;
            }

            if ($lockedPurchase->status !== CapacityPurchaseStatus::PENDING) {
                throw new CheckoutUnavailable('This capacity purchase cannot be completed.');
            }

            $subscription = Subscription::query()
                ->with('plan.features')
                ->whereKey($lockedPurchase->subscription_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureSubscriptionCanReceiveCapacity($lockedPurchase, $subscription);

            $configuration = $this->pricing->configuration($subscription->plan);

            if (! $configuration instanceof CapacityConfiguration) {
                throw new CheckoutUnavailable('The subscription no longer supports capacity.');
            }

            $currentCapacity = $this->pricing->currentCapacity($subscription);
            $newCapacity = $currentCapacity + $lockedPurchase->quantity;

            if ($configuration->maximumCapacity !== null
                && $newCapacity > $configuration->maximumCapacity) {
                throw new CheckoutUnavailable('Completing this purchase would exceed the plan capacity limit.');
            }

            $subscription->update(['capacity_count' => $newCapacity]);
            $lockedPurchase->update([
                'previous_capacity' => $currentCapacity,
                'new_capacity' => $newCapacity,
                'status' => CapacityPurchaseStatus::COMPLETED,
                'failure_message' => null,
                'completed_at' => now(),
            ]);

            return $lockedPurchase->refresh();
        }, 3);
    }

    private function ensureSubscriptionCanReceiveCapacity(
        CapacityPurchase $purchase,
        Subscription $subscription,
    ): void {
        if ($subscription->subscribable_type !== (new Workspace)->getMorphClass()
            || (int) $subscription->subscribable_id !== $purchase->workspace_id
            || ! in_array($subscription->status, [
                SubscriptionStatus::Active,
                SubscriptionStatus::Trialing,
            ], true)) {
            throw new CheckoutUnavailable('The subscription is not active for this workspace.');
        }

        if ($subscription->ends_at === null
            || ! $subscription->ends_at->equalTo($purchase->term_ends_at)
            || ! $purchase->term_ends_at->isFuture()) {
            throw new CheckoutUnavailable('The subscription term changed before payment completed.');
        }
    }
}
