<?php

namespace App\Http\Controllers\Api\Consultations;

use App\Actions\Consultations\ConfirmConsultationReservationAction;
use App\Http\Requests\Consultations\ConfirmConsultationReservationRequest;
use App\Http\Resources\ConsultationReservationResource;
use App\Models\Consultations\Consultation;

final readonly class ConfirmConsultationReservationController
{
    public function __construct(private ConfirmConsultationReservationAction $confirmReservation) {}

    public function __invoke(
        ConfirmConsultationReservationRequest $request,
        Consultation $consultation,
    ): ConsultationReservationResource {
        return new ConsultationReservationResource(
            $this->confirmReservation->execute(
                $consultation,
                $request->integer('appointment_id'),
            ),
        );
    }
}
