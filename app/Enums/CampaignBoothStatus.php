<?php

namespace App\Enums;

enum CampaignBoothStatus: string
{
    case Requested = 'requested';
    case Active = 'active';
    case GracePeriod = 'grace_period';
    case Suspended = 'suspended';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Awaiting deployment',
            self::Active => 'Active',
            self::GracePeriod => 'Grace period',
            self::Suspended => 'Suspended',
            self::Inactive => 'Inactive',
        };
    }
}
