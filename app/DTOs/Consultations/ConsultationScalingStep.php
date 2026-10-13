<?php

namespace App\DTOs\Consultations;

final readonly class ConsultationScalingStep
{
    public function __construct(
        public int $capacity,
        public int $additionalCapacity,
        public ConsultationQuotaBreakdown $scheduled,
        public ConsultationQuotaBreakdown $instant,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'capacity' => $this->capacity,
            'additionalCapacity' => $this->additionalCapacity,
            'scheduled' => $this->scheduled->toArray(),
            'instant' => $this->instant->toArray(),
        ];
    }
}
