<?php

namespace App\DTOs\Payments;

use App\Enums\Subscriptions\IndividualPlanBillingCycle;
use App\ValueObjects\Money;
use Carbon\CarbonInterface;

final readonly class BillingCycleUpgradeQuote
{
    public function __construct(
        public IndividualPlanBillingCycle $currentCycle,
        public IndividualPlanBillingCycle $targetCycle,
        public Money $currentAmount,
        public Money $targetAmount,
        public Money $amountDueNow,
        public CarbonInterface $currentEndsAt,
        public CarbonInterface $targetEndsAt,
        public CarbonInterface $quotedAt,
    ) {}
}
