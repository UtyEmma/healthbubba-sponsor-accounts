<?php

namespace App\DTOs\Payments;

final readonly class PlanChangeEligibility
{
    /** @param list<string> $violations */
    public function __construct(
        public int $occupiedCapacity,
        public int $targetCapacityCount,
        public array $violations,
    ) {}

    public function available(): bool
    {
        return $this->violations === [];
    }
}
