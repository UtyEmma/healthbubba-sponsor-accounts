<?php

namespace App\Http\Resources;

use App\DTOs\Consultations\ConsultationSponsorAvailabilityData;
use App\DTOs\Consultations\ConsultationTypeAvailabilityData;
use App\DTOs\Consultations\PatientConsultationSponsorshipData;
use App\Enums\Consultations\ConsultationType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PatientConsultationSponsorshipResource extends JsonResource
{
    /** @return list<array<string, mixed>> */
    public function toArray(Request $request): array
    {
        /** @var PatientConsultationSponsorshipData $result */
        $result = $this->resource;

        return array_map(
            fn (ConsultationSponsorAvailabilityData $sponsor): array => [
                'sponsor' => [
                    'id' => $sponsor->id,
                    'name' => $sponsor->name,
                    'type' => [
                        'value' => $sponsor->type->value,
                        'label' => $sponsor->type->label(),
                    ],
                ],
                'campaign' => $sponsor->campaign === null ? null : [
                    'id' => $sponsor->campaign->id,
                    'name' => $sponsor->campaign->name,
                    'slug' => $sponsor->campaign->slug,
                    'description' => $sponsor->campaign->description,
                    'location' => $sponsor->campaign->location,
                    'city' => $sponsor->campaign->city,
                    'state' => $sponsor->campaign->state,
                    'country' => $sponsor->campaign->country,
                    'status' => $sponsor->campaign->status->value,
                    'startsAt' => $sponsor->campaign->startsAt?->toISOString(),
                    'endsAt' => $sponsor->campaign->endsAt?->toISOString(),
                ],
                'limits' => [
                    ConsultationType::GeneralPractitioner->value => $this->availability(
                        $sponsor,
                        ConsultationType::GeneralPractitioner,
                    ),
                    ConsultationType::Specialist->value => $this->availability(
                        $sponsor,
                        ConsultationType::Specialist,
                    ),
                ],
            ],
            $result->sponsors,
        );
    }

    /** @return array<string, mixed> */
    private function availability(
        ConsultationSponsorAvailabilityData $sponsor,
        ConsultationType $type,
    ): array {
        $availability = collect($sponsor->consultationTypes)
            ->first(fn (ConsultationTypeAvailabilityData $item): bool => $item->type === $type);

        if (! $availability instanceof ConsultationTypeAvailabilityData) {
            return [
                'label' => $type->label(),
                'available' => false,
                'reason' => 'feature_unavailable',
                'allocated' => null,
                'used' => 0,
                'reserved' => 0,
                'remaining' => 0,
                'periodStartsAt' => null,
                'periodEndsAt' => null,
            ];
        }

        return [
            'label' => $availability->type->label(),
            'available' => $availability->available,
            'reason' => $availability->reason,
            'allocated' => $availability->allocatedUnits,
            'used' => $availability->usedUnits,
            'reserved' => $availability->reservedUnits,
            'remaining' => $availability->remainingUnits,
            'periodStartsAt' => $availability->periodStartsAt?->toISOString(),
            'periodEndsAt' => $availability->periodEndsAt?->toISOString(),
        ];
    }
}
