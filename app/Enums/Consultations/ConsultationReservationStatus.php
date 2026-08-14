<?php

namespace App\Enums\Consultations;

enum ConsultationReservationStatus: string
{
    case Reserved = 'reserved';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
