<?php

namespace App\Http\Controllers\Api\Consultations;

use App\Actions\Consultations\ReserveConsultationAction;
use App\DTOs\Consultations\ConsultationEligibilityData;
use App\Http\Requests\Consultations\StoreConsultationEligibilityRequest;
use App\Http\Resources\ConsultationEligibilityResource;

final readonly class CheckConsultationEligibilityController
{
    public function __construct(private ReserveConsultationAction $reserveConsultation) {}

    public function __invoke(StoreConsultationEligibilityRequest $request): ConsultationEligibilityResource
    {
        return new ConsultationEligibilityResource(
            $this->reserveConsultation->execute(
                ConsultationEligibilityData::fromRequest($request),
            ),
        );
    }
}
