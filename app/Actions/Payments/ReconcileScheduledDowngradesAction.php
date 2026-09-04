<?php

namespace App\Actions\Payments;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\Subscriptions\PlanChangeDirection;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Services\Payments\PlanChangeEligibilityService;
use App\Services\Payments\PlanChangePricingService;
use Illuminate\Support\Facades\DB;

final readonly class ReconcileScheduledDowngradesAction
{
    public function __construct(
        private PlanChangePricingService $pricing,
        private PlanChangeEligibilityService $eligibility,
        private WorkspaceActivityLogger $activities,
    ) {}

    /** @return array{applied: int, cancelled: int} */
    public function execute(): array
    {
        $applied = 0;
        $cancelled = 0;

        Subscription::query()
            ->whereNotNull('scheduled_plan_id')
            ->with(['plan.features', 'scheduledPlan.features'])
            ->orderBy('id')
            ->eachById(function (Subscription $subscription) use (&$applied, &$cancelled): void {
                $result = $this->reconcile($subscription->getKey());
                $applied += $result === 'applied' ? 1 : 0;
                $cancelled += $result === 'cancelled' ? 1 : 0;
            }, 100);

        return ['applied' => $applied, 'cancelled' => $cancelled];
    }

    private function reconcile(int $subscriptionId): string
    {
        return DB::transaction(function () use ($subscriptionId): string {
            $subscription = Subscription::query()
                ->with(['plan.features', 'scheduledPlan.features'])
                ->whereKey($subscriptionId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $subscription->scheduledPlan instanceof Plan) {
                return 'skipped';
            }

            $workspace = Workspace::query()->whereKey($subscription->subscribable_id)->lockForUpdate()->first();

            if (! $workspace instanceof Workspace) {
                return 'skipped';
            }

            $targetPlan = $subscription->scheduledPlan;
            $violations = [];

            if ($this->pricing->direction($subscription, $targetPlan) !== PlanChangeDirection::DOWNGRADE) {
                $violations[] = 'The scheduled plan is no longer lower-priced than the current plan.';
            } else {
                $eligibility = $this->eligibility->assess($workspace, $subscription, $targetPlan);
                $violations = $eligibility->violations;
            }

            if ($violations !== []) {
                $subscription->update([
                    'scheduled_plan_id' => null,
                    'scheduled_plan_change_at' => null,
                ]);
                $this->record($workspace, $subscription, $targetPlan, false, $violations);

                return 'cancelled';
            }

            $subscription->update([
                'plan_id' => $targetPlan->getKey(),
                'capacity_count' => $eligibility->targetCapacityCount,
                'scheduled_plan_id' => null,
                'scheduled_plan_change_at' => null,
            ]);
            $this->record($workspace, $subscription, $targetPlan, true, []);

            return 'applied';
        }, 3);
    }

    /** @param list<string> $violations */
    private function record(
        Workspace $workspace,
        Subscription $subscription,
        Plan $targetPlan,
        bool $applied,
        array $violations,
    ): void {
        $this->activities->record($workspace, new WorkspaceActivityData(
            type: $applied
                ? WorkspaceActivityType::PlanDowngradeApplied
                : WorkspaceActivityType::PlanDowngradeCancelled,
            title: $applied
                ? "Downgraded to {$targetPlan->name}"
                : "Cancelled downgrade to {$targetPlan->name}",
            actor: WorkspaceActivityActor::system(),
            subjectType: 'subscription',
            subjectId: $subscription->getKey(),
            subjectName: $targetPlan->name,
            description: $applied
                ? 'The previously scheduled downgrade was applied immediately.'
                : implode(' ', $violations),
            context: [
                'plan_name' => $targetPlan->name,
                'violations' => $violations,
            ],
        ));
    }
}
