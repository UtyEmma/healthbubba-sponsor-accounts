<?php

namespace App\DTOs\Payments;

final readonly class PlanChangeResult
{
    public function __construct(
        public PlanChangeQuote $quote,
        public ?CheckoutSession $checkoutSession,
    ) {}
}
