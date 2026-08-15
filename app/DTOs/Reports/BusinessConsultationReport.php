<?php

namespace App\DTOs\Reports;

final readonly class BusinessConsultationReport
{
    /** @param list<WorkforceStatus> $workforce */
    public function __construct(
        public int $activeEmployees,
        public ?int $gpConsultationsRemaining,
        public bool $gpConsultationsUnlimited,
        public ?string $gpConsultationsUnavailableReason,
        public int $activationRate,
        public array $workforce,
    ) {}
}
