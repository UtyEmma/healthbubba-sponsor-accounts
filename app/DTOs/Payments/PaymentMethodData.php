<?php

namespace App\DTOs\Payments;

final readonly class PaymentMethodData
{
    /** @param array<string, mixed> $authorizationData */
    public function __construct(
        public string $authorizationCode,
        public string $email,
        public ?string $customerCode,
        public string $channel,
        public bool $reusable,
        public ?string $signature,
        public ?string $cardType,
        public ?string $lastFour,
        public ?string $expiryMonth,
        public ?string $expiryYear,
        public ?string $bank,
        public ?string $countryCode,
        public ?string $accountName,
        public array $authorizationData,
    ) {}
}
