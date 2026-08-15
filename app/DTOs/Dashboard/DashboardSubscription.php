<?php

namespace App\DTOs\Dashboard;

use Carbon\CarbonImmutable;

final readonly class DashboardSubscription
{
    public function __construct(
        public string $planName,
        public string $status,
        public string $statusLabel,
        public bool $active,
        public string $renewalAmount,
        public string $billingCycleLabel,
        public int $includedCapacity,
        public int $currentCapacity,
        public int $additionalCapacity,
        public ?CarbonImmutable $renewsAt,
        public ?int $renewalDays,
    ) {}
}
