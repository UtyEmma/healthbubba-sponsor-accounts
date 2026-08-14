<?php

namespace App\DTOs\Consultations;

use App\Enums\Consultations\ConsultationAllocationScope;
use App\Enums\Consultations\ConsultationType;
use App\Models\Consultations\Appointment;
use Carbon\CarbonImmutable;

final readonly class ConsultationViewData
{
    public function __construct(
        public Appointment $appointment,
        public ConsultationType $type,
        public string $featureSlug,
        public ?string $planName,
        public ConsultationAllocationScope $scope,
        public ?CarbonImmutable $scheduledAt,
    ) {}
}
