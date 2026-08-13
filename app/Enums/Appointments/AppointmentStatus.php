<?php

namespace App\Enums;

enum AppointmentStatus:int {

    case UPCOMING = 0;
    case COMPLETED = 1;
    case CANCELLED = 2; 

    function label(){
        return match($this) {
            self::UPCOMING => 'Upcoming',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            default => 'Unknown Status'
        };
    }

    static function fromValue(string $value): ?self {
        return match($value) {
            'upcoming' => self::UPCOMING,
            'completed' => self::COMPLETED,
            'cancelled' => self::CANCELLED,
            default => null
        };
    }
}