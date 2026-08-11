<?php

namespace App\DTOs\Payments;

use App\Enums\Payments\PaymentGatewayName;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;

final readonly class StartPlanCheckoutData
{
    public function __construct(
        public Workspace $workspace,
        public User $user,
        public Plan $plan,
        public int $additionalCapacity,
        public string $callbackUrl,
        public ?PaymentGatewayName $gateway = null,
    ) {}
}
