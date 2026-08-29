<?php

namespace App\Enums;

enum CampaignUsageBenefit: string
{
    case GeneralPractitioner = 'gp';
    case Specialist = 'specialist';
    case Medication = 'medication';
    case Laboratory = 'laboratory';

    public function label(): string
    {
        return match ($this) {
            self::GeneralPractitioner => 'GP consultation',
            self::Specialist => 'Specialist consultation',
            self::Medication => 'Medication',
            self::Laboratory => 'Laboratory',
        };
    }
}
