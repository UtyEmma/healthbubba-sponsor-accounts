<?php

namespace App\Queries\Plans;

use App\Enums\AccountTypes;
use App\Enums\Subscriptions\Features;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Revoltify\Subscriptionify\Enums\FeatureType;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;
use Revoltify\Subscriptionify\Models\Subscription;

final class GetPlanBillingData
{
    /**
     * @return array{
     *     accountType: string,
     *     accountTypeLabel: string,
     *     plans: list<array<string, mixed>>,
     *     subscription: array<string, mixed>|null
     * }
     */
    public function execute(User $user): array
    {
        $accountType = $user->type ?? AccountTypes::INDIVIDUAL;
        $subscriber = $this->resolveSubscriber($user, $accountType);

        $subscription = Subscription::query()
            ->with('plan')
            ->whereMorphedTo('subscribable', $subscriber)
            ->latest('starts_at')
            ->latest('id')
            ->first();

        $plans = Plan::query()
            ->active()
            ->forAccountType($accountType)
            ->with(['features' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $featureCatalog = $this->featureCatalog($plans);

        return [
            'accountType' => $accountType->value,
            'accountTypeLabel' => $accountType->label(),
            'plans' => array_values($plans
                ->map(fn (Plan $plan): array => $this->planData($plan, $featureCatalog, $subscription))
                ->all()),
            'subscription' => $subscription === null ? null : $this->subscriptionData($subscription),
        ];
    }

    private function resolveSubscriber(User $user, AccountTypes $accountType): Model
    {
        if ($accountType === AccountTypes::INDIVIDUAL) {
            return $user;
        }

        $organization = new Organization;

        return $user->organizations()
            ->where($organization->qualifyColumn('type'), $accountType->value)
            ->latest($organization->qualifyColumn('id'))
            ->first() ?? $user;
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
     * @return array<string, mixed>
     */
    private function planData(Plan $plan, Collection $featureCatalog, ?Subscription $subscription): array
    {
        $isCurrent = $subscription?->plan_id === $plan->getKey();
        $currentSortOrder = $subscription?->plan instanceof Plan
            ? $subscription->plan->sort_order
            : null;
        $features = $plan->features->keyBy('slug');

        return [
            'id' => $plan->getKey(),
            'name' => $plan->name,
            'slug' => $plan->slug,
            'description' => $plan->description,
            'price' => $plan->price,
            'billingLabel' => $this->billingLabel($plan),
            'isCurrent' => $isCurrent,
            'action' => $this->planAction($plan, $isCurrent, $currentSortOrder),
            'features' => $featureCatalog
                ->map(function (Feature $catalogFeature) use ($features): array {
                    /** @var Feature|null $feature */
                    $feature = $features->get($catalogFeature->slug);
                    $pivot = $feature?->pivot;

                    return [
                        'slug' => $catalogFeature->slug,
                        'label' => Features::tryFrom($catalogFeature->slug)?->label() ?? $catalogFeature->name,
                        'description' => $catalogFeature->description,
                        'type' => $catalogFeature->type->value,
                        'included' => $feature !== null,
                        'value' => $pivot instanceof FeaturePlan && $catalogFeature->type !== FeatureType::Toggle
                            ? $pivot->getValue()
                            : null,
                        'unitPrice' => $pivot instanceof FeaturePlan && (float) $pivot->getUnitPrice() > 0
                            ? $pivot->getUnitPrice()
                            : null,
                        'resetLabel' => $pivot instanceof FeaturePlan
                            ? $this->resetLabel($pivot)
                            : null,
                    ];
                })
                ->all(),
        ];
    }

    private function planAction(Plan $plan, bool $isCurrent, ?int $currentSortOrder): string
    {
        if ($isCurrent) {
            return 'current';
        }

        if ($currentSortOrder === null) {
            return 'select';
        }

        return $plan->sort_order < $currentSortOrder ? 'downgrade' : 'upgrade';
    }

    private function billingLabel(Plan $plan): string
    {
        $interval = $plan->billing_interval->value;
        $cadence = $plan->billing_period === 1
            ? "per {$interval}"
            : "every {$plan->billing_period} {$interval}s";

        return $plan->account_type === AccountTypes::BUSINESS
            ? "{$cadence} per seat"
            : $cadence;
    }

    private function resetLabel(FeaturePlan $pivot): ?string
    {
        $interval = $pivot->getResetInterval();
        $period = $pivot->getResetPeriod();

        if ($interval === null || $period === null) {
            return null;
        }

        return $period === 1
            ? "per {$interval->value}"
            : "every {$period} {$interval->value}s";
    }

    /** @return array<string, mixed> */
    private function subscriptionData(Subscription $subscription): array
    {
        $plan = $subscription->plan;

        return [
            'id' => $subscription->getKey(),
            'status' => $subscription->status->value,
            'statusLabel' => str($subscription->status->value)->headline()->toString(),
            'isValid' => $subscription->valid(),
            'plan' => $plan instanceof Plan ? [
                'id' => $plan->getKey(),
                'name' => $plan->name,
                'price' => $plan->price,
                'billingLabel' => $this->billingLabel($plan),
            ] : null,
            'startsAt' => $subscription->starts_at->toISOString(),
            'endsAt' => $subscription->ends_at?->toISOString(),
            'trialEndsAt' => $subscription->trial_ends_at?->toISOString(),
            'cancelledAt' => $subscription->cancelled_at?->toISOString(),
            'renewedAt' => $subscription->renewed_at?->toISOString(),
        ];
    }
}
