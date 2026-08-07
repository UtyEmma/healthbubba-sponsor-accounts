<?php

namespace App\Enums;

enum AccountTypes: string
{
    case INDIVIDUAL = 'individual';
    case BUSINESS = 'business';
    case INSTITUTION = 'institution';

    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Individual Sponsor',
            self::BUSINESS => 'Business Sponsor',
            self::INSTITUTION => 'Institutional Sponsor',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::INDIVIDUAL->value => self::INDIVIDUAL->label(),
            self::BUSINESS->value => self::BUSINESS->label(),
            self::INSTITUTION->value => self::INSTITUTION->label(),
        ];
    }
}
