<?php

namespace App\DTOs\Consultations;

final readonly class ConsultationSponsorPlanData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
    ) {}
}
