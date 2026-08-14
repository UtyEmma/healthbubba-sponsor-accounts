<?php

namespace App\DTOs\Consultations;

use App\Http\Requests\Consultations\StoreConsultationEligibilityRequest;

final readonly class ConsultationEligibilityData
{
    public function __construct(
        public int $sponsorId,
        public int $patientId,
        public int $doctorId,
    ) {}

    public static function fromRequest(StoreConsultationEligibilityRequest $request): self
    {
        return new self(
            sponsorId: $request->integer('sponsor_id'),
            patientId: $request->integer('patient_id'),
            doctorId: $request->integer('doctor_id'),
        );
    }
}
