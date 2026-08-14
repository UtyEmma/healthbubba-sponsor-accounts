<?php

namespace App\DTOs\Consultations;

final readonly class ConsultationQuotaBreakdown
{
    public function __construct(
        public ?int $base,
        public ?int $additional,
        public ?int $total,
    ) {}

    /** @return array{base: int|null, additional: int|null, total: int|null} */
    public function toArray(): array
    {
        return [
            'base' => $this->base,
            'additional' => $this->additional,
            'total' => $this->total,
        ];
    }
}
