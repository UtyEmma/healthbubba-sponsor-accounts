<?php

namespace App\DTOs\CapacityPurchases;

use App\DTOs\Payments\CheckoutSession;
use App\Models\CapacityPurchase;

final readonly class CapacityPurchaseResult
{
    public function __construct(
        public CapacityPurchase $purchase,
        public ?CheckoutSession $checkoutSession,
    ) {}
}
