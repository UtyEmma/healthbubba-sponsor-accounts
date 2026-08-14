<?php

namespace App\Services\Consultations;

use App\Enums\AccountTypes;
use App\Enums\Consultations\ConsultationType;
use App\Enums\Subscriptions\Features;
use Illuminate\Support\Str;

final class ConsultationTypeResolver
{
    public function resolve(?string $providerType): ConsultationType
    {
        $normalized = Str::of($providerType ?? '')
            ->squish()
            ->lower()
            ->toString();

        return in_array($normalized, ['gp', 'general practitioner'], true)
            ? ConsultationType::GeneralPractitioner
            : ConsultationType::Specialist;
    }

    public function feature(AccountTypes $accountType, ConsultationType $type): Features
    {
        if ($accountType === AccountTypes::BUSINESS) {
            return $type === ConsultationType::GeneralPractitioner
                ? Features::GP_CONSULTATIONS_PER_SEAT
                : Features::SPECIALIST_CONSULTATIONS_PER_SEAT;
        }

        return $type === ConsultationType::GeneralPractitioner
            ? Features::GP_CONSULTATIONS
            : Features::SPECIALIST_CONSULTATIONS;
    }
}
