<?php

namespace App\Enums\Appointments;

enum AppointmentStatus: int
{
    case Upcoming = 0;
    case Completed = 1;
    case Cancelled = 2;

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Upcoming',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}
