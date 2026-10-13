<?php

namespace App\DTOs\Reports;

final readonly class BusinessConsultationReport
{
    /** @param list<WorkforceStatus> $workforce */
    public function __construct(
        public int $activeEmployees,
        public ?int $scheduledConsultationsRemaining,
        public bool $scheduledConsultationsUnlimited,
        public ?string $scheduledConsultationsUnavailableReason,
        public int $activationRate,
        public array $workforce,
    ) {}
}
