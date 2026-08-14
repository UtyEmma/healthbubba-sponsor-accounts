<?php

namespace App\DTOs\Consultations;

final readonly class ConsultationUsage
{
    public function __construct(
        public int $completed,
        public int $reserved,
    ) {}

    public function total(): int
    {
        return $this->completed + $this->reserved;
    }
}
