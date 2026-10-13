<?php

namespace App\Enums\Consultations;

enum ConsultationType: string
{
    case Scheduled = 'scheduled';
    case Instant = 'instant';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled Consultation',
            self::Instant => 'Instant Consultation',
        };
    }
}
