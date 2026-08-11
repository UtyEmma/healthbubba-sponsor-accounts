<?php

namespace App\DTOs\CapacityPurchases;

use App\Enums\CapacityPurchases\CapacityPaymentSource;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;

final readonly class StartCapacityPurchaseData
{
    public function __construct(
        public Workspace $workspace,
        public User $user,
        public Subscription $subscription,
        public int $quantity,
        public CapacityPaymentSource $paymentSource,
        public string $callbackUrl,
    ) {}
}
