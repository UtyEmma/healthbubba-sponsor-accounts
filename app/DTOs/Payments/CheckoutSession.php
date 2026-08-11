<?php

namespace App\DTOs\Payments;

use App\Enums\Payments\PaymentGatewayName;

final readonly class CheckoutSession
{
    public function __construct(
        public PaymentGatewayName $gateway,
        public string $reference,
        public string $authorizationUrl,
        public string $accessCode,
    ) {}
}
