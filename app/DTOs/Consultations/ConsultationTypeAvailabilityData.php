<?php

namespace App\DTOs\Consultations;

use App\Enums\Consultations\ConsultationType;
use Carbon\CarbonImmutable;

final readonly class ConsultationTypeAvailabilityData
{
    public function __construct(
        public ConsultationType $type,
        public bool $available,
        public ?string $reason,
        public ?string $coverageName,
        public ?int $allocatedUnits,
        public int $usedUnits,
        public int $reservedUnits,
        public ?int $remainingUnits,
        public ?CarbonImmutable $periodStartsAt,
        public ?CarbonImmutable $periodEndsAt,
    ) {}
}
