<?php

namespace App\Enums;

enum CampaignBoothStatus: string
{
    case Requested = 'requested';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Awaiting deployment',
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }
}
