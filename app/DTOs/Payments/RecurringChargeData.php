<?php

namespace App\DTOs\Payments;

use App\ValueObjects\Money;
use InvalidArgumentException;

final readonly class RecurringChargeData
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public Money $amount,
        public string $email,
        public string $authorizationCode,
        public string $reference,
        public array $metadata = [],
    ) {
        if ($this->amount->amountInMinorUnits === 0) {
            throw new InvalidArgumentException('A recurring charge amount must be greater than zero.');
        }
    }
}
