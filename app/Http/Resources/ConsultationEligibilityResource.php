<?php

namespace App\Http\Resources;

use App\DTOs\Consultations\ConsultationEligibilityResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConsultationEligibilityResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var ConsultationEligibilityResult $result */
        $result = $this->resource;

        return [
            'available' => $result->available,
            'reason' => $result->reason,
            'consultationType' => $result->type === null ? null : [
                'value' => $result->type->value,
                'label' => $result->type->label(),
            ],
            'reservation' => $result->reservation === null
                ? null
                : (new ConsultationReservationResource($result->reservation))->resolve($request),
        ];
    }
}
