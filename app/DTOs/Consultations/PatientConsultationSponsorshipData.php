<?php

namespace App\DTOs\Consultations;

final readonly class PatientConsultationSponsorshipData
{
    /** @param list<ConsultationSponsorAvailabilityData> $sponsors */
    public function __construct(
        public int $patientId,
        public array $sponsors,
    ) {}
}
