<?php

namespace App\DTOs\Campaigns;

final readonly class CampaignBoothData
{
    public function __construct(
        public bool $required,
        public ?int $count,
        public ?string $preferredDeploymentDate,
        public ?string $site,
        public ?string $contactName,
        public ?string $contactPhone,
    ) {}
}
