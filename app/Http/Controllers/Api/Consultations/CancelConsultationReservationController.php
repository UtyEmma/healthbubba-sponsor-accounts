<?php

namespace App\Http\Controllers\Api\Consultations;

use App\Actions\Consultations\CancelConsultationReservationAction;
use App\Http\Requests\Consultations\CancelConsultationReservationRequest;
use App\Http\Resources\ConsultationReservationResource;
use App\Models\Consultations\Appointment;

final readonly class CancelConsultationReservationController
{
    public function __construct(private CancelConsultationReservationAction $cancelReservation) {}

    public function __invoke(
        CancelConsultationReservationRequest $request,
        Appointment $appointment,
    ): ConsultationReservationResource {
        return new ConsultationReservationResource(
            $this->cancelReservation->execute($appointment),
        );
    }
}
