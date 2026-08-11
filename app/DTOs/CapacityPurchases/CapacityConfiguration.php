<?php

namespace App\DTOs\CapacityPurchases;

use App\ValueObjects\Money;

final readonly class CapacityConfiguration
{
    public function __construct(
        public string $unit,
        public string $unitPlural,
        public int $includedCapacity,
        public ?int $maximumCapacity,
        public ?Money $unitPrice,
        public bool $purchasesEnabled,
        public ?string $unavailableReason,
    ) {}

    public function additionalCapacity(int $capacity): int
    {
        return max(0, $capacity - max(1, $this->includedCapacity));
    }

    public function remainingPurchasableCapacity(int $capacity): ?int
    {
        if ($this->maximumCapacity === null) {
            return null;
        }

        return max(0, $this->maximumCapacity - $capacity);
    }
}
