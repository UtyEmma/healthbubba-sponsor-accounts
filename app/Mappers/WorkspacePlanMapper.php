<?php

namespace App\Mappers;

use App\DTOs\WorkspacePlan;
use App\Enums\AccountTypes;
use App\Models\Plan;
use App\Models\Workspace;
use App\Services\Payments\CapacityPricingService;
use App\Support\Billing\QuotaDescriptionFormatter;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;

final readonly class WorkspacePlanMapper
{
    public function __construct(
        private QuotaDescriptionFormatter $quotaDescriptions,
        private CapacityPricingService $capacityPricing,
    ) {}

    public function map(Workspace $workspace, Plan $plan): WorkspacePlan
    {
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
        );
    }

    /** @return Collection<int, WorkspacePlan> */
    public function mapAvailable(Workspace $workspace): Collection
    {
        $availablePlans = $this->availablePlans($workspace);
        $featureCatalog = $this->featureCatalog($availablePlans);

        return $availablePlans->map(
            fn (Plan $plan): WorkspacePlan => $this->mapPlan(
                workspace: $workspace,
                plan: $plan,
                featureCatalog: $featureCatalog,
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
    ): WorkspacePlan {
        $mappedFeatures = $this->mapFeatureCatalog($plan, $featureCatalog);
        $isCurrent = $workspace->onPlan($plan);
        $checkout = $this->checkoutState($workspace, $plan, $isCurrent);
        $capacity = $this->capacityPricing->configuration($plan);

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
            unavailableReason: $checkout['reason'],
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
    private function checkoutState(Workspace $workspace, Plan $plan, bool $isCurrent): array
    {
        if ($isCurrent) {
            return [
                'available' => false,
                'reason' => 'This is your current plan.',
            ];
        }

        if ($workspace->subscribed()) {
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
