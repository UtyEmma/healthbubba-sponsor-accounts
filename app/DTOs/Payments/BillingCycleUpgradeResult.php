<?php

namespace App\DTOs\Payments;

final readonly class BillingCycleUpgradeResult
{
    public function __construct(
        public BillingCycleUpgradeQuote $quote,
        public ?CheckoutSession $checkoutSession,
    ) {}
}
