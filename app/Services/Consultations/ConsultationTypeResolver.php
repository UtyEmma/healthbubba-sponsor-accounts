<?php

namespace App\Services\Consultations;

use App\Enums\AccountTypes;
use App\Enums\Consultations\ConsultationType;
use App\Enums\Subscriptions\Features;

final class ConsultationTypeResolver
{
    public function resolve(?bool $isUrgentConsultation): ConsultationType
    {
        return $isUrgentConsultation === true
            ? ConsultationType::Instant
            : ConsultationType::Scheduled;
    }

    public function feature(AccountTypes $accountType, ConsultationType $type): Features
    {
        if ($accountType === AccountTypes::BUSINESS) {
            return $this->perSeatFeature($type);
        }

        return $this->baseFeature($type);
    }

    public function baseFeature(ConsultationType $type): Features
    {
        return $type === ConsultationType::Scheduled
            ? Features::SCHEDULED_CONSULTATIONS
            : Features::INSTANT_CONSULTATIONS;
    }

    public function perSeatFeature(ConsultationType $type): Features
    {
        return $type === ConsultationType::Scheduled
            ? Features::SCHEDULED_CONSULTATIONS_PER_SEAT
            : Features::INSTANT_CONSULTATIONS_PER_SEAT;
    }
}
