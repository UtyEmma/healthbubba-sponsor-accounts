<?php

namespace App\DTOs\CapacityPurchases;

use Illuminate\Contracts\Support\Arrayable;

/** @implements Arrayable<string, mixed> */
final readonly class CapacityPurchaseSummary implements Arrayable
{
    public function __construct(
        public int $subscriptionId,
        public string $unit,
        public string $unitPlural,
        public int $currentCapacity,
        public int $includedCapacity,
        public ?int $maximumCapacity,
        public ?string $unitPrice,
        public ?string $proratedUnitPrice,
        public string $currency,
        public string $walletBalance,
        public bool $available,
        public ?string $unavailableReason,
        public ?string $termEndsAt,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'unit' => $this->unit,
            'unit_plural' => $this->unitPlural,
            'current_capacity' => $this->currentCapacity,
            'included_capacity' => $this->includedCapacity,
            'maximum_capacity' => $this->maximumCapacity,
            'unit_price' => $this->unitPrice,
            'prorated_unit_price' => $this->proratedUnitPrice,
            'currency' => $this->currency,
            'wallet_balance' => $this->walletBalance,
            'available' => $this->available,
            'unavailable_reason' => $this->unavailableReason,
            'term_ends_at' => $this->termEndsAt,
        ];
    }
}
