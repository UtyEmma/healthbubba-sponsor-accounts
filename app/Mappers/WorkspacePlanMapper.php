<?php

namespace App\Mappers;

use App\DTOs\WorkspacePlan;
use App\Enums\AccountTypes;
use App\Enums\Subscriptions\PlanChangeDirection;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\Payments\CapacityPricingService;
use App\Services\Payments\PlanChangePricingService;
use App\Services\Payments\PlanChangeEligibilityService;
use App\Support\Billing\QuotaDescriptionFormatter;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;

final readonly class WorkspacePlanMapper
{
    public function __construct(
        private QuotaDescriptionFormatter $quotaDescriptions,
        private CapacityPricingService $capacityPricing,
        private PlanChangePricingService $planChangePricing,
        private PlanChangeEligibilityService $planChangeEligibility,
    ) {}

    public function map(
        Workspace $workspace,
        Plan $plan,
        ?Subscription $subscription = null,
    ): WorkspacePlan {
        if ($plan->account_type !== $workspace->type) {
            throw new InvalidArgumentException(
                "Plan [{$plan->getKey()}] does not belong to the workspace account type.",
            );
        }

        $availablePlans = $this->availablePlans($workspace);

        $availablePlan = $availablePlans->find($plan->getKey());

        if (! $availablePlan instanceof Plan) {
            throw new InvalidArgumentException(
                "Plan [{$plan->getKey()}] is not available for this workspace.",
            );
        }

        return $this->mapPlan(
            workspace: $workspace,
            plan: $availablePlan,
            featureCatalog: $this->featureCatalog($availablePlans),
            subscription: $subscription,
        );
    }

    /** @return Collection<int, WorkspacePlan> */
    public function mapAvailable(
        Workspace $workspace,
        ?Subscription $subscription = null,
    ): Collection {
        $availablePlans = $this->availablePlans($workspace);
        $featureCatalog = $this->featureCatalog($availablePlans);

        return $availablePlans->map(
            fn (Plan $plan): WorkspacePlan => $this->mapPlan(
                workspace: $workspace,
                plan: $plan,
                featureCatalog: $featureCatalog,
                subscription: $subscription,
            ),
        );
    }

    /** @return EloquentCollection<int, Plan> */
    private function availablePlans(Workspace $workspace): EloquentCollection
    {
        return Plan::query()
            ->active()
            ->forAccountType($workspace->type)
            ->with(['features' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /** @param Collection<int, Feature> $featureCatalog */
    private function mapPlan(
        Workspace $workspace,
        Plan $plan,
        Collection $featureCatalog,
        ?Subscription $subscription,
    ): WorkspacePlan {
        $mappedFeatures = $this->mapFeatureCatalog($plan, $featureCatalog);
        $isCurrent = $subscription instanceof Subscription
            ? (int) $subscription->plan_id === (int) $plan->getKey()
            : $workspace->onPlan($plan);
        $checkout = $this->checkoutState($workspace, $plan, $isCurrent, $subscription);
        $capacity = $this->capacityPricing->configuration($plan);
        $planChange = $this->planChangeState($workspace, $subscription, $plan, $isCurrent);

        return new WorkspacePlan(
            id: (int) $plan->getKey(),
            name: $plan->name,
            slug: $plan->slug,
            description: $plan->description,
            price: $plan->price,
            cadence: $this->billingCadence($plan),
            currency: config()->string('payments.currency', 'NGN'),
            isCurrent: $isCurrent,
            checkoutAvailable: $checkout['available'],
            includedSeats: $plan->account_type === AccountTypes::BUSINESS
                ? $plan->included_seats
                : null,
            additionalSeatPrice: $plan->account_type === AccountTypes::BUSINESS
                ? $plan->additional_seat_price
                : null,
            allowsCapacityPurchases: $plan->allows_capacity_purchases,
            capacity: $capacity === null ? null : [
                'unit' => $capacity->unit,
                'unit_plural' => $capacity->unitPlural,
                'included' => $capacity->includedCapacity,
                'maximum' => $capacity->maximumCapacity,
                'additional_unit_price' => $capacity->unitPrice?->toMajorAmount(),
                'purchases_enabled' => $capacity->purchasesEnabled,
                'unavailable_reason' => $capacity->unavailableReason,
            ],
            planChange: $planChange,
            unavailableReason: $planChange['unavailable_reason'] ?? $checkout['reason'],
            features: $mappedFeatures['features'],
            quotas: $mappedFeatures['quotas'],
        );
    }

    private function billingCadence(Plan $plan): string
    {
        $interval = $plan->billing_interval->value;

        return $plan->billing_period === 1
            ? "per {$interval}"
            : "every {$plan->billing_period} {$interval}s";
    }

    /** @return array{available: bool, reason: string|null} */
    private function checkoutState(
        Workspace $workspace,
        Plan $plan,
        bool $isCurrent,
        ?Subscription $subscription,
    ): array {
        if ($isCurrent) {
            return [
                'available' => false,
                'reason' => 'This is your current plan.',
            ];
        }

        if ($subscription instanceof Subscription && $subscription->valid()) {
            return [
                'available' => false,
                'reason' => 'An active subscription is already attached to this workspace.',
            ];
        }

        if ($plan->account_type === AccountTypes::INSTITUTION) {
            return [
                'available' => false,
                'reason' => 'Contact us to arrange institutional billing.',
            ];
        }

        if ($plan->isFree()) {
            return [
                'available' => false,
                'reason' => 'This plan does not require online checkout.',
            ];
        }

        if ($plan->trial_days > 0) {
            return [
                'available' => false,
                'reason' => 'Online checkout is unavailable for plans with a trial period.',
            ];
        }

        if ((float) $plan->price <= 0) {
            return [
                'available' => false,
                'reason' => 'A paid price has not been configured for this plan.',
            ];
        }

        if (
            $plan->account_type === AccountTypes::BUSINESS
            && ($plan->included_seats === null || $plan->included_seats < 1)
        ) {
            return [
                'available' => false,
                'reason' => 'The included seats have not been configured for this plan.',
            ];
        }

        if (
            $plan->account_type === AccountTypes::BUSINESS
            && $plan->allows_capacity_purchases
            && ($plan->additional_seat_price === null || (float) $plan->additional_seat_price <= 0)
        ) {
            return [
                'available' => false,
                'reason' => 'The additional-seat price has not been configured for this plan.',
            ];
        }

        return [
            'available' => true,
            'reason' => null,
        ];
    }

    /**
     * @return array{
     *     available: bool,
     *     direction: string|null,
     *     amount_due_now: string,
     *     renewal_amount: string,
     *     effective_at: string,
     *     scheduled: bool,
     *     target_capacity_count: int,
     *     limit_violations: list<string>,
     *     unavailable_reason: string|null
     * }|null
     */
    private function planChangeState(
        Workspace $workspace,
        ?Subscription $subscription,
        Plan $plan,
        bool $isCurrent,
    ): ?array {
        if (! $subscription instanceof Subscription
            || $subscription->status !== SubscriptionStatus::Active
            || $isCurrent) {
            return null;
        }

        $violations = [];

        try {
            $direction = $this->planChangePricing->direction($subscription, $plan);
            $quoteSubscription = $subscription;

            if ($direction === PlanChangeDirection::DOWNGRADE) {
                $eligibility = $this->planChangeEligibility->assess($workspace, $subscription, $plan);
                $violations = $eligibility->violations;
                $quoteSubscription = clone $subscription;
                $quoteSubscription->setAttribute('capacity_count', $eligibility->targetCapacityCount);
            }

            $quote = $this->planChangePricing->quote($quoteSubscription, $plan);
        } catch (CheckoutUnavailable $exception) {
            return [
                'available' => false,
                'direction' => null,
                'amount_due_now' => '0.00',
                'renewal_amount' => $plan->price,
                'effective_at' => $subscription->ends_at?->toISOString() ?? '',
                'scheduled' => false,
                'target_capacity_count' => $subscription->capacity_count,
                'limit_violations' => $violations,
                'unavailable_reason' => $exception->getMessage(),
            ];
        }

        $available = $violations === [];
        $unavailableReason = $available ? null : implode(' ', $violations);

        return [
            'available' => $available,
            'direction' => $quote->direction->value,
            'amount_due_now' => $quote->amountDueNow->toMajorAmount(),
            'renewal_amount' => $quote->targetRenewal->toMajorAmount(),
            'effective_at' => $quote->effectiveAt->toISOString(),
            'scheduled' => false,
            'target_capacity_count' => $quote->targetCapacityCount,
            'limit_violations' => $violations,
            'unavailable_reason' => $unavailableReason,
        ];
    }

    /**
     * @param  EloquentCollection<int, Plan>  $plans
     * @return Collection<int, Feature>
     */
    private function featureCatalog(EloquentCollection $plans): Collection
    {
        return $plans
            ->flatMap(fn (Plan $plan): EloquentCollection => $plan->features)
            ->unique('slug')
            ->sortBy('sort_order')
            ->values();
    }

    /**
     * @param  Collection<int, Feature>  $featureCatalog
     * @return array{
     *     features: list<array{
     *         slug: string,
     *         name: string,
     *         description: string|null,
     *         type: string,
     *         included: bool,
     *         value: string|null,
     *         unitPrice: string|null
     *     }>,
     *     quotas: list<array{
     *         name: string,
     *         slug: string,
     *         quota: string|null,
     *         description: string
     *     }>
     * }
     */
    private function mapFeatureCatalog(Plan $plan, Collection $featureCatalog): array
    {
        $planFeatures = $plan->features->keyBy(
            fn (Feature $feature): string => $feature->slug,
        );
        $includedFeatures = [];
        $excludedFeatures = [];
        $quotas = [];

        foreach ($featureCatalog as $feature) {
            $isIncluded = $planFeatures->has($feature->slug);
            $includedFeature = $isIncluded
                ? $planFeatures->get($feature->slug)
                : null;
            $limits = $this->featureAssignment($includedFeature);

            if ($feature->hasQuota()) {
                $quota = $limits?->getValue();

                $quotas[] = [
                    'name' => $feature->name,
                    'slug' => $feature->slug,
                    'quota' => $quota,
                    'description' => $this->quotaDescriptions->format(
                        feature: $feature,
                        assignment: $limits,
                        plan: $plan,
                    ),
                ];

                continue;
            }

            $mappedFeature = [
                'slug' => $feature->slug,
                'name' => $feature->name,
                'description' => $feature->description,
                'type' => $feature->type->value,
                'included' => $isIncluded,
                'value' => $limits?->getValue(),
                'unitPrice' => $limits?->getUnitPrice(),
            ];

            if (! $isIncluded) {
                $excludedFeatures[] = $mappedFeature;

                continue;
            }

            $includedFeatures[] = $mappedFeature;
        }

        return [
            'features' => [...$includedFeatures, ...$excludedFeatures],
            'quotas' => $quotas,
        ];
    }

    private function featureAssignment(?Feature $feature): ?FeaturePlan
    {
        if ($feature === null || ! $feature->relationLoaded('limits')) {
            return null;
        }

        $assignment = $feature->getRelation('limits');

        return $assignment instanceof FeaturePlan ? $assignment : null;
    }
}
