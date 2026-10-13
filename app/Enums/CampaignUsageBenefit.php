<?php

namespace App\Enums;

enum CampaignUsageBenefit: string
{
    case ScheduledConsultation = 'scheduled';
    case InstantConsultation = 'instant';
    case Medication = 'medication';
    case Laboratory = 'laboratory';

    public function label(): string
    {
        return match ($this) {
            self::ScheduledConsultation => 'Scheduled consultation',
            self::InstantConsultation => 'Instant consultation',
            self::Medication => 'Medication',
            self::Laboratory => 'Laboratory',
        };
    }
}
