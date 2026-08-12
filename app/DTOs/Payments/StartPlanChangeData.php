<?php

namespace App\DTOs\Payments;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;

final readonly class StartPlanChangeData
{
    public function __construct(
        public Workspace $workspace,
        public User $user,
        public Subscription $subscription,
        public Plan $targetPlan,
        public string $callbackUrl,
    ) {}
}
