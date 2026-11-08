<?php

namespace App\DTOs\Payments;

use App\Enums\Subscriptions\IndividualPlanBillingCycle;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;

final readonly class ScheduleSubscriptionBillingCycleData
{
    public function __construct(
        public Workspace $workspace,
        public User $user,
        public Subscription $subscription,
        public IndividualPlanBillingCycle $billingCycle,
    ) {}
}
