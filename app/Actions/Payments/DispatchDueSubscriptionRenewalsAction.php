<?php

namespace App\Actions\Payments;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Jobs\Payments\ChargeSubscriptionRenewal;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\Activity\WorkspaceActivityLogger;
use Illuminate\Support\Facades\DB;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class DispatchDueSubscriptionRenewalsAction
{
    public function __construct(private WorkspaceActivityLogger $activities) {}

    public function execute(): void
    {
        $workspaceType = (new Workspace)->getMorphClass();

        Subscription::query()
            ->where('subscribable_type', $workspaceType)
            ->where('status', SubscriptionStatus::Active)
            ->where('auto_renew', false)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->select('id')
            ->chunkById(100, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    DB::transaction(function () use ($subscription): void {
                        $locked = Subscription::query()
                            ->whereKey($subscription->getKey())
                            ->lockForUpdate()
                            ->firstOrFail();

                        if ($locked->status !== SubscriptionStatus::Active
                            || $locked->auto_renew
                            || $locked->ends_at?->isFuture() !== false) {
                            return;
                        }

                        $locked->update(['status' => SubscriptionStatus::Expired]);
                        $workspace = Workspace::query()->find($locked->subscribable_id);

                        if ($workspace instanceof Workspace) {
                            $planName = $locked->plan()->value('name') ?? 'subscription';
                            $this->activities->record($workspace, new WorkspaceActivityData(
                                type: WorkspaceActivityType::SubscriptionExpired,
                                title: 'Subscription expired',
                                actor: WorkspaceActivityActor::system(),
                                subjectType: 'subscription',
                                subjectId: $locked->getKey(),
                                subjectName: $planName,
                                context: ['plan_name' => $planName],
                            ));
                        }
                    }, 3);
                }
            });

        Subscription::query()
            ->where('subscribable_type', $workspaceType)
            ->where('status', SubscriptionStatus::Active)
            ->where('auto_renew', true)
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->whereNotNull('renewal_retry_at')
                        ->where('renewal_retry_at', '<=', now());
                })->orWhere(function ($query): void {
                    $query->whereNull('renewal_retry_at')
                        ->whereNotNull('next_charge_at')
                        ->where('next_charge_at', '<=', now());
                });
            })
            ->select('id')
            ->chunkById(100, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    ChargeSubscriptionRenewal::dispatch((int) $subscription->getKey());
                }
            });
    }
}
