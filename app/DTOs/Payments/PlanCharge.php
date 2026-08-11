<?php

namespace App\DTOs\Payments;

use App\ValueObjects\Money;

final readonly class PlanCharge
{
    public function __construct(
        public Money $money,
        public ?int $capacityCount,
        public int $additionalCapacity,
    ) {}
}
