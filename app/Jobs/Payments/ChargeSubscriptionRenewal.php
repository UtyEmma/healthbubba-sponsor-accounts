<?php

namespace App\Jobs\Payments;

use App\Actions\Payments\ChargeSubscriptionRenewalAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

final class ChargeSubscriptionRenewal implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly int $subscriptionId)
    {
        $this->onQueue('payments');
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("subscription-renewal:{$this->subscriptionId}"))
                ->dontRelease()
                ->expireAfter(120),
        ];
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(ChargeSubscriptionRenewalAction $action): void
    {
        $action->execute($this->subscriptionId);
    }
}
