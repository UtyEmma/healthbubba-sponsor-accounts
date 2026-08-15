<?php

namespace App\DTOs\Dashboard;

final readonly class ConsultationTrend
{
    public function __construct(
        public string $month,
        public int $consultations,
    ) {}
}
