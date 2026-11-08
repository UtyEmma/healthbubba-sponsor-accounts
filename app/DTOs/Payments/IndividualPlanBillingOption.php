<?php

namespace App\DTOs\Payments;

use App\Enums\Subscriptions\IndividualPlanBillingCycle;
use App\ValueObjects\Money;

final readonly class IndividualPlanBillingOption
{
    public function __construct(
        public IndividualPlanBillingCycle $cycle,
        public Money $price,
        public int $savingsPercentage,
        public bool $available,
    ) {}

    /** @return array{cycle: string, label: string, months: int, price: string, cadence: string, savings_percentage: int, available: bool} */
    public function toArray(): array
    {
        return [
            'cycle' => $this->cycle->value,
            'label' => $this->cycle->label(),
            'months' => $this->cycle->months(),
            'price' => $this->price->toMajorAmount(),
            'cadence' => $this->cycle->cadence(),
            'savings_percentage' => $this->savingsPercentage,
            'available' => $this->available,
        ];
    }
}
