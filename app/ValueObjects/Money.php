<?php

namespace App\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    public string $currency;

    public function __construct(
        public int $amountInMinorUnits,
        string $currency = 'NGN',
    ) {
        if ($this->amountInMinorUnits < 0) {
            throw new InvalidArgumentException('A monetary amount cannot be negative.');
        }

        $currency = mb_strtoupper(trim($currency));

        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new InvalidArgumentException('Currency must be a three-letter ISO 4217 code.');
        }

        $this->currency = $currency;
    }

    public static function fromMajor(string|int $amount, string $currency = 'NGN'): self
    {
        $amount = trim((string) $amount);

        if (preg_match('/^(0|[1-9]\d*)(?:\.(\d{1,8}))?$/', $amount, $matches) !== 1) {
            throw new InvalidArgumentException('Amount must be a positive decimal value with at most two decimal places.');
        }

        $wholeUnits = $matches[1];
        $fractionalUnits = str_pad($matches[2] ?? '', 2, '0');
        $maximumWholeUnits = (string) intdiv(PHP_INT_MAX - 99, 100);

        if (mb_strlen($wholeUnits) > mb_strlen($maximumWholeUnits)
            || (mb_strlen($wholeUnits) === mb_strlen($maximumWholeUnits)
                && strcmp($wholeUnits, $maximumWholeUnits) > 0)) {
            throw new InvalidArgumentException('Amount exceeds the supported range.');
        }

        $minorUnits = ((int) $wholeUnits * 100) + (int) $fractionalUnits;

        return new self($minorUnits, $currency);
    }

    public function toMajorAmount(): string
    {
        return sprintf(
            '%d.%02d',
            intdiv($this->amountInMinorUnits, 100),
            $this->amountInMinorUnits % 100,
        );
    }

    public function equals(self $money): bool
    {
        return $this->amountInMinorUnits === $money->amountInMinorUnits
            && $this->currency === $money->currency;
    }

    public function add(self $money): self
    {
        $this->assertSameCurrency($money);

        if ($money->amountInMinorUnits > PHP_INT_MAX - $this->amountInMinorUnits) {
            throw new InvalidArgumentException('Amount exceeds the supported range.');
        }

        return new self(
            $this->amountInMinorUnits + $money->amountInMinorUnits,
            $this->currency,
        );
    }

    public function multiply(int $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('A monetary multiplier cannot be negative.');
        }

        if ($multiplier !== 0 && $this->amountInMinorUnits > intdiv(PHP_INT_MAX, $multiplier)) {
            throw new InvalidArgumentException('Amount exceeds the supported range.');
        }

        return new self($this->amountInMinorUnits * $multiplier, $this->currency);
    }

    private function assertSameCurrency(self $money): void
    {
        if ($this->currency !== $money->currency) {
            throw new InvalidArgumentException('Money values must use the same currency.');
        }
    }
}
