<?php

namespace App\DTOs\Consultations;

final readonly class ConsultationScalingStep
{
    public function __construct(
        public int $capacity,
        public int $additionalCapacity,
        public ConsultationQuotaBreakdown $gp,
        public ConsultationQuotaBreakdown $specialist,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'capacity' => $this->capacity,
            'additionalCapacity' => $this->additionalCapacity,
            'gp' => $this->gp->toArray(),
            'specialist' => $this->specialist->toArray(),
        ];
    }
}
