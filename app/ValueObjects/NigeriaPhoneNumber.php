<?php

namespace App\ValueObjects;

use InvalidArgumentException;

final readonly class NigeriaPhoneNumber
{
    private function __construct(public string $value) {}

    public static function from(string $phone): self
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '234'.substr($digits, 1);
        }

        if (! preg_match('/^234\d{10}$/', $digits)) {
            throw new InvalidArgumentException('Enter a valid Nigerian phone number.');
        }

        return new self('+'.$digits);
    }

    public static function normalize(string $phone): string
    {
        try {
            return self::from($phone)->value;
        } catch (InvalidArgumentException) {
            return trim($phone);
        }
    }
}
