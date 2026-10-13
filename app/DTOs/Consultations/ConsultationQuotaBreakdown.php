<?php

namespace App\DTOs\Consultations;

final readonly class ConsultationQuotaBreakdown
{
    public function __construct(
        public bool $available,
        public ?int $base,
        public ?int $additional,
        public ?int $total,
    ) {}

    /** @return array{available: bool, base: int|null, additional: int|null, total: int|null} */
    public function toArray(): array
    {
        return [
            'available' => $this->available,
            'base' => $this->base,
            'additional' => $this->additional,
            'total' => $this->total,
        ];
    }
}
