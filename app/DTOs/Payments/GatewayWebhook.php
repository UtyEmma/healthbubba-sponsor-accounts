<?php

namespace App\DTOs\Payments;

use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentStatus;

final readonly class GatewayWebhook
{
    /** @param array<string, bool|int|string|null> $data Sanitized event data. */
    public function __construct(
        public PaymentGatewayName $gateway,
        public string $event,
        public ?string $reference,
        public ?PaymentStatus $paymentStatus,
        public array $data = [],
    ) {}
}
