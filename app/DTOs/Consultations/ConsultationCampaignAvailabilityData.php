<?php

namespace App\DTOs\Consultations;

use App\Enums\CampaignStatus;
use Carbon\CarbonImmutable;

final readonly class ConsultationCampaignAvailabilityData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $location,
        public ?string $city,
        public ?string $state,
        public ?string $country,
        public CampaignStatus $status,
        public ?CarbonImmutable $startsAt,
        public ?CarbonImmutable $endsAt,
    ) {}
}
