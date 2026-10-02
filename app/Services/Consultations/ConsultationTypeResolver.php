<?php

namespace App\Services\Consultations;

use App\Enums\AccountTypes;
use App\Enums\Consultations\ConsultationType;
use App\Enums\Subscriptions\Features;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ConsultationTypeResolver
{
    public function resolve($specialtyId): ConsultationType
    {
        if(!$specialtyId) return ConsultationType::GeneralPractitioner;

        if(!$specialty = DB::connection('main')->table('specialties')->whereSpecialtyId($specialtyId)->first()) return ConsultationType::GeneralPractitioner;
        
        return in_array($specialty->specialty_name, ['gp', 'general practitioner', 'General Practitioner (GP)'], true)
            ? ConsultationType::GeneralPractitioner
            : ConsultationType::Specialist;
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
        return $type === ConsultationType::GeneralPractitioner
            ? Features::GP_CONSULTATIONS
            : Features::SPECIALIST_CONSULTATIONS;
    }

    public function perSeatFeature(ConsultationType $type): Features
    {
        return $type === ConsultationType::GeneralPractitioner
            ? Features::GP_CONSULTATIONS_PER_SEAT
            : Features::SPECIALIST_CONSULTATIONS_PER_SEAT;
    }
}
