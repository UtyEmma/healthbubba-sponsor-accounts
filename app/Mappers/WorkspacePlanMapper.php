<?php

namespace App\Mappers;

use App\DTOs\WorkspacePlan;
use App\Models\Plan;
use App\Models\Workspace;
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

        return new WorkspacePlan(
            id: (int) $plan->getKey(),
            name: $plan->name,
            slug: $plan->slug,
            description: $plan->description,
            price: $plan->price,
            isCurrent: $workspace->onPlan($plan),
            features: $mappedFeatures['features'],
            quotas: $mappedFeatures['quotas'],
        );
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
        $planFeatures = $plan->features->keyBy('slug');
        $includedFeatures = [];
        $excludedFeatures = [];
        $quotas = [];

        foreach ($featureCatalog as $feature) {
            /** @var Feature|null $includedFeature */
            $includedFeature = $planFeatures->get($feature->slug);
            $limits = $includedFeature?->limits;
            $limits = $limits instanceof FeaturePlan ? $limits : null;

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
                'included' => $includedFeature !== null,
                'value' => $limits?->getValue(),
                'unitPrice' => $limits?->getUnitPrice(),
            ];

            if ($includedFeature === null) {
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
}
