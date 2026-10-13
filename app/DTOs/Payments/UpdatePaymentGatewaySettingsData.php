<?php

namespace App\DTOs\Payments;

use App\Enums\AccountTypes;
use App\Enums\Payments\PaymentGatewayName;

final readonly class UpdatePaymentGatewaySettingsData
{
    public function __construct(
        public PaymentGatewayName $individual,
        public PaymentGatewayName $business,
        public PaymentGatewayName $institution,
    ) {}

    /** @param array<string, string> $gateways */
    public static function fromArray(array $gateways): self
    {
        return new self(
            individual: PaymentGatewayName::from($gateways[AccountTypes::INDIVIDUAL->value]),
            business: PaymentGatewayName::from($gateways[AccountTypes::BUSINESS->value]),
            institution: PaymentGatewayName::from($gateways[AccountTypes::INSTITUTION->value]),
        );
    }

    public function gatewayFor(AccountTypes $accountType): PaymentGatewayName
    {
        return match ($accountType) {
            AccountTypes::INDIVIDUAL => $this->individual,
            AccountTypes::BUSINESS => $this->business,
            AccountTypes::INSTITUTION => $this->institution,
        };
    }

    /** @return array<string, PaymentGatewayName> */
    public function all(): array
    {
        return [
            AccountTypes::INDIVIDUAL->value => $this->individual,
            AccountTypes::BUSINESS->value => $this->business,
            AccountTypes::INSTITUTION->value => $this->institution,
        ];
    }
}
