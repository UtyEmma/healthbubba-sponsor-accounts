<?php

namespace App\DTOs\Payments;

use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentStatus;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;

final readonly class PaymentVerification
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, bool|int|string|null>  $providerData  Sanitized provider fields safe for transient processing.
     */
    public function __construct(
        public PaymentGatewayName $gateway,
        public string $reference,
        public PaymentStatus $status,
        public Money $amount,
        public string $customerEmail,
        public ?string $providerTransactionId = null,
        public ?CarbonImmutable $paidAt = null,
        public ?PaymentMethodData $paymentMethod = null,
        public ?string $authorizationUrl = null,
        public ?string $gatewayResponse = null,
        public array $metadata = [],
        public array $providerData = [],
    ) {}
}
