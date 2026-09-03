<?php

namespace App\DTOs\WorkspaceBeneficiaries;

final readonly class CapacitySummary
{
    public function __construct(
        public int $used,
        public int $total,
        public ?string $unavailableReason = null,
        public bool $unlimited = false,
    ) {}

    public function remaining(): int
    {
        return max(0, $this->total - $this->used);
    }

    public function canInvite(): bool
    {
        return $this->unavailableReason === null
            && ($this->unlimited || $this->remaining() > 0);
    }
}
