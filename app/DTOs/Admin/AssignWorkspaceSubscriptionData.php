<?php

namespace App\DTOs\Admin;

use App\Enums\Subscriptions\SubscriptionFundingSource;

final readonly class AssignWorkspaceSubscriptionData
{
    public function __construct(
        public int $workspaceId,
        public int $planId,
        public SubscriptionFundingSource $fundingSource,
        public int $additionalCapacity = 0,
        public bool $complimentaryRenews = false,
    ) {}
}
