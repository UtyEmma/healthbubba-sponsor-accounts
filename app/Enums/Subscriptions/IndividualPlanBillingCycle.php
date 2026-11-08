<?php

namespace App\Enums\Subscriptions;

use Carbon\CarbonInterface;
use Revoltify\Subscriptionify\Enums\Interval;

enum IndividualPlanBillingCycle: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case BIANNUAL = 'biannual';
    case ANNUAL = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::BIANNUAL => 'Biannual',
            self::ANNUAL => 'Annual',
        };
    }

    public function months(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::QUARTERLY => 3,
            self::BIANNUAL => 6,
            self::ANNUAL => 12,
        };
    }

    public function cadence(): string
    {
        return match ($this) {
            self::MONTHLY => 'per month',
            self::QUARTERLY => 'every 3 months',
            self::BIANNUAL => 'every 6 months',
            self::ANNUAL => 'per year',
        };
    }

    public function billingPeriod(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::QUARTERLY => 3,
            self::BIANNUAL => 6,
            self::ANNUAL => 1,
        };
    }

    public function billingInterval(): Interval
    {
        return $this === self::ANNUAL ? Interval::Year : Interval::Month;
    }

    public function calculateEndsAt(CarbonInterface $startsAt): CarbonInterface
    {
        return $this->billingInterval()->addToDate($startsAt, $this->billingPeriod());
    }
}
