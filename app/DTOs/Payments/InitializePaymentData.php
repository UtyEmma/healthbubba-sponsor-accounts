<?php

namespace App\DTOs\Payments;

use App\ValueObjects\Money;
use InvalidArgumentException;

final readonly class InitializePaymentData
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  list<string>|null  $channels
     */
    public function __construct(
        public Money $amount,
        public string $email,
        public string $reference,
        public ?string $callbackUrl = null,
        public array $metadata = [],
        public ?array $channels = null,
    ) {
        if ($this->amount->amountInMinorUnits === 0) {
            throw new InvalidArgumentException('A payment amount must be greater than zero.');
        }
    }
}
