<?php

namespace App\DTOs\Consultations;

use App\Enums\Consultations\ConsultationAllocationScope;
use App\Enums\Consultations\ConsultationType;
use Carbon\CarbonImmutable;

final readonly class ConsultationAllocation
{
    public function __construct(
        public int $subscriptionId,
        public ?int $planId,
        public string $planName,
        public ConsultationType $type,
        public string $featureSlug,
        public ConsultationAllocationScope $scope,
        public ?int $workspaceBeneficiaryId,
        public ?int $limit,
        public CarbonImmutable $periodStart,
        public CarbonImmutable $periodEnd,
    ) {}
}
