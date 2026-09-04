<?php

namespace App\Http\Controllers\Api\Consultations;

use App\Actions\Consultations\ReserveAppointmentConsultationAction;
use App\DTOs\Consultations\ReserveConsultationData;
use App\Http\Requests\Consultations\StoreConsultationReservationRequest;
use App\Http\Resources\ConsultationReservationResource;

final readonly class StoreConsultationReservationController
{
    public function __construct(
        private ReserveAppointmentConsultationAction $reserveConsultation,
    ) {}

    public function __invoke(
        StoreConsultationReservationRequest $request,
    ): ConsultationReservationResource {
        return new ConsultationReservationResource(
            $this->reserveConsultation->execute(
                ReserveConsultationData::fromRequest($request),
            ),
        );
    }
}
