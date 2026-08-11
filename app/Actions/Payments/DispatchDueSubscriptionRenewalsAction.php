<?php

namespace App\Actions\Payments;

use App\Jobs\Payments\ChargeSubscriptionRenewal;
use App\Models\Subscription;
use App\Models\Workspace;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final class DispatchDueSubscriptionRenewalsAction
{
    public function execute(): void
    {
        $workspaceType = (new Workspace)->getMorphClass();

        Subscription::query()
            ->where('subscribable_type', $workspaceType)
            ->where('status', SubscriptionStatus::Active)
            ->where('auto_renew', false)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->update(['status' => SubscriptionStatus::Expired]);

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
