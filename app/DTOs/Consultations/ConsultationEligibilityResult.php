<?php

namespace App\DTOs\Consultations;

use App\Enums\Consultations\ConsultationType;
use App\Models\Consultations\Consultation;

final readonly class ConsultationEligibilityResult
{
    public function __construct(
        public bool $available,
        public ?string $reason,
        public ?ConsultationType $type,
        public ?Consultation $reservation,
    ) {}

    public static function unavailable(string $reason, ?ConsultationType $type = null): self
    {
        return new self(false, $reason, $type, null);
    }

    public static function available(Consultation $reservation): self
    {
        return new self(true, null, $reservation->consultation_type, $reservation);
    }
}
