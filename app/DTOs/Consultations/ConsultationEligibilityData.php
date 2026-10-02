<?php

namespace App\DTOs\Consultations;

use App\Models\Consultations\Appointment;

final readonly class ConsultationEligibilityData
{
    public function __construct(
        public int $sponsorId,
        public int $appointmentId,
        public int $patientId,
        public ?Appointment $appointment,
        public ?int $campaignId = null,
    ) {}
}
