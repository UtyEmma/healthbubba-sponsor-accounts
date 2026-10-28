<?php

namespace App\Actions\Admin;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\Subscriptions\SubscriptionFundingSource;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\Activity\WorkspaceActivityLogger;
use Illuminate\Support\Facades\DB;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class RenewComplimentarySubscriptionAction
{
    public function __construct(private WorkspaceActivityLogger $activities) {}

    public function execute(int $subscriptionId): bool
    {
        return DB::transaction(function () use ($subscriptionId): bool {
            $subscription = Subscription::query()
                ->with('plan')
                ->whereKey($subscriptionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($subscription->funding_source !== SubscriptionFundingSource::Complimentary
                || ! $subscription->complimentary_renews
                || ! $subscription->auto_renew
                || $subscription->next_charge_at?->isFuture() !== false
                || ! in_array($subscription->status, [SubscriptionStatus::Active, SubscriptionStatus::PastDue], true)) {
                return false;
            }

            $workspace = Workspace::query()->whereKey($subscription->subscribable_id)->lockForUpdate()->firstOrFail();
            $now = now();
            $base = $subscription->ends_at?->isFuture() === true ? $subscription->ends_at : $now;
            $endsAt = $subscription->plan->calculateEndsAt($base);

            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'starts_at' => $now,
                'ends_at' => $endsAt,
                'renewed_at' => $now,
                'next_charge_at' => $endsAt,
                'renewal_attempts' => 0,
                'renewal_retry_at' => null,
                'cancelled_at' => null,
            ]);

            $this->activities->record($workspace, new WorkspaceActivityData(
                type: WorkspaceActivityType::SubscriptionRenewed,
                title: "Renewed {$subscription->plan->name}",
                actor: WorkspaceActivityActor::system(),
                subjectType: 'subscription',
                subjectId: $subscription->getKey(),
                subjectName: $subscription->plan->name,
                description: 'The ongoing complimentary subscription renewed without a charge.',
                context: ['funding_source' => SubscriptionFundingSource::Complimentary->value],
            ));

            return true;
        }, 3);
    }
}
