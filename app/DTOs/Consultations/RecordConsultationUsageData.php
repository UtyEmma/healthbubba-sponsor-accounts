<?php

namespace App\DTOs\Consultations;

use App\Http\Requests\Consultations\RecordConsultationUsageRequest;

final readonly class RecordConsultationUsageData
{
    public function __construct(
        public int $appointmentId,
        public int $sponsorId,
    ) {}

    public static function fromRequest(RecordConsultationUsageRequest $request): self
    {
        return new self(
            appointmentId: $request->integer('appointment_id'),
            sponsorId: $request->integer('sponsor_id'),
        );
    }
}
