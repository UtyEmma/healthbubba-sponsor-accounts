<?php

namespace App\DTOs\Consultations;

final readonly class ConsultationEligibilityData
{
    public function __construct(
        public int $sponsorId,
        public int $appointmentId,
        public int $patientId,
        public int $doctorId,
        public ?int $campaignId = null,
    ) {}
}
