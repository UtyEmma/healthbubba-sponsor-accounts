<?php

namespace App\Enums\Consultations;

enum ConsultationType: string
{
    case GeneralPractitioner = 'gp';
    case Specialist = 'specialist';

    public function label(): string
    {
        return match ($this) {
            self::GeneralPractitioner => 'Scheduled Consultation',
            self::Specialist => 'Instant Consultation',
        };
    }
}
