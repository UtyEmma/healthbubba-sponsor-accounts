<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case PENDING = 'PENDING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Upcoming',
            self::IN_PROGRESS => 'Active',
            self::COMPLETED => 'Completed',
        };
    }
}
