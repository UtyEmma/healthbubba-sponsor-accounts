<?php

namespace App\DTOs\Payments;

use App\Models\Subscription;

final readonly class SubscriptionCheckoutResult
{
    public function __construct(
        public ?Subscription $subscription,
        public ?CheckoutSession $checkoutSession,
    ) {}
}
