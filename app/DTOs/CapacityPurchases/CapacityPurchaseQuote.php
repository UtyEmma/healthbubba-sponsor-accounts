<?php

namespace App\DTOs\CapacityPurchases;

use App\ValueObjects\Money;
use Carbon\CarbonInterface;

final readonly class CapacityPurchaseQuote
{
    public function __construct(
        public CapacityConfiguration $configuration,
        public int $quantity,
        public int $previousCapacity,
        public int $newCapacity,
        public Money $unitPrice,
        public Money $proratedUnitPrice,
        public Money $total,
        public Money $renewalIncrease,
        public CarbonInterface $termStartsAt,
        public CarbonInterface $termEndsAt,
    ) {}
}
