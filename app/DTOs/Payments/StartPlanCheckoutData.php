<?php

namespace App\DTOs\Payments;

use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\SubscriptionPaymentSource;
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
        public SubscriptionPaymentSource $paymentSource = SubscriptionPaymentSource::WALLET,
        public ?PaymentGatewayName $gateway = null,
    ) {}
}
