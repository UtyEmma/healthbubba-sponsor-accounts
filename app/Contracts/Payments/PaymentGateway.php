<?php

namespace App\Contracts\Payments;

use App\DTOs\Payments\CheckoutSession;
use App\DTOs\Payments\GatewayWebhook;
use App\DTOs\Payments\InitializePaymentData;
use App\DTOs\Payments\PaymentVerification;
use App\Enums\Payments\PaymentGatewayName;

interface PaymentGateway
{
    public function name(): PaymentGatewayName;

    public function initialize(InitializePaymentData $data): CheckoutSession;

    public function verify(string $reference): PaymentVerification;

    /** @param array<string, mixed> $headers */
    public function parseWebhook(string $rawPayload, array $headers): GatewayWebhook;
}
