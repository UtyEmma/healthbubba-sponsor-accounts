<?php

namespace App\DTOs\Payments;

use App\Enums\Payments\SubscriptionPaymentSource;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;

final readonly class StartSubscriptionRenewalData
{
    public function __construct(
        public Workspace $workspace,
        public User $user,
        public Subscription $subscription,
        public SubscriptionPaymentSource $paymentSource,
        public string $callbackUrl,
    ) {}
}
