<?php

namespace App\DTOs\Consultations;

use App\Http\Requests\Consultations\StoreConsultationReservationRequest;

final readonly class ReserveConsultationData
{
    public function __construct(
        public int $appointmentId,
        public int $sponsorId,
    ) {}

    public static function fromRequest(StoreConsultationReservationRequest $request): self
    {
        return new self(
            appointmentId: $request->integer('appointment_id'),
            sponsorId: $request->integer('sponsor_id'),
        );
    }
}
