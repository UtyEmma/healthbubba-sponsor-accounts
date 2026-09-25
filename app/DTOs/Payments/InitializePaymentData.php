<?php

namespace App\DTOs\Payments;

use App\Enums\Payments\PaymentCheckoutProfile;
use App\ValueObjects\Money;
use InvalidArgumentException;

final readonly class InitializePaymentData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public Money $amount,
        public string $email,
        public string $reference,
        public string $description,
        public ?string $callbackUrl = null,
        public array $metadata = [],
        public PaymentCheckoutProfile $profile = PaymentCheckoutProfile::FLEXIBLE,
        public ?string $customerReference = null,
    ) {
        if ($this->amount->amountInMinorUnits === 0) {
            throw new InvalidArgumentException('A payment amount must be greater than zero.');
        }
    }
}
