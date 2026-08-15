<?php

namespace App\DTOs\Reports;

final readonly class WorkforceStatus
{
    public function __construct(
        public string $status,
        public string $label,
        public int $count,
        public float $percentage,
    ) {}
}
