<?php

namespace App\DTOs\Payments;

use App\Enums\Subscriptions\PlanChangeDirection;
use App\ValueObjects\Money;
use Carbon\CarbonInterface;

final readonly class PlanChangeQuote
{
    public function __construct(
        public PlanChangeDirection $direction,
        public Money $currentBasePrice,
        public Money $targetBasePrice,
        public Money $currentRenewal,
        public Money $targetRenewal,
        public Money $amountDueNow,
        public int $targetCapacityCount,
        public int $additionalCapacity,
        public CarbonInterface $effectiveAt,
        public CarbonInterface $quotedAt,
        public CarbonInterface $termEndsAt,
    ) {}
}
