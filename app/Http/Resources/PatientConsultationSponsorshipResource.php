<?php

namespace App\Http\Resources;

use App\DTOs\Consultations\ConsultationSponsorAvailabilityData;
use App\DTOs\Consultations\ConsultationTypeAvailabilityData;
use App\DTOs\Consultations\PatientConsultationSponsorshipData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PatientConsultationSponsorshipResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var PatientConsultationSponsorshipData $result */
        $result = $this->resource;

        return [
            'patientId' => $result->patientId,
            'sponsors' => array_map(
                fn (ConsultationSponsorAvailabilityData $sponsor): array => [
                    'id' => $sponsor->id,
                    'name' => $sponsor->name,
                    'type' => [
                        'value' => $sponsor->type->value,
                        'label' => $sponsor->type->label(),
                    ],
                    'consultationTypes' => array_map(
                        fn (ConsultationTypeAvailabilityData $availability): array => [
                            'value' => $availability->type->value,
                            'label' => $availability->type->label(),
                            'available' => $availability->available,
                            'reason' => $availability->reason,
                            'coverageName' => $availability->coverageName,
                            'allocatedUnits' => $availability->allocatedUnits,
                            'usedUnits' => $availability->usedUnits,
                            'reservedUnits' => $availability->reservedUnits,
                            'remainingUnits' => $availability->remainingUnits,
                            'periodStartsAt' => $availability->periodStartsAt?->toISOString(),
                            'periodEndsAt' => $availability->periodEndsAt?->toISOString(),
                        ],
                        $sponsor->consultationTypes,
                    ),
                ],
                $result->sponsors,
            ),
        ];
    }
}
