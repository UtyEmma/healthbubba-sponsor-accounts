<?php

namespace App\Enums;

enum CampaignBudgetCategory: string
{
    case Medication = 'medication';
    case Laboratory = 'laboratory';

    public function label(): string
    {
        return match ($this) {
            self::Medication => 'Medication',
            self::Laboratory => 'Laboratory',
        };
    }
}
