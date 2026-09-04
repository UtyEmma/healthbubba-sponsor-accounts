<?php

namespace App\DTOs\Consultations;

use App\Enums\AccountTypes;

final readonly class ConsultationSponsorAvailabilityData
{
    /** @param list<ConsultationTypeAvailabilityData> $consultationTypes */
    public function __construct(
        public int $id,
        public string $name,
        public AccountTypes $type,
        public array $consultationTypes,
        public ?ConsultationCampaignAvailabilityData $campaign = null,
    ) {}
}
