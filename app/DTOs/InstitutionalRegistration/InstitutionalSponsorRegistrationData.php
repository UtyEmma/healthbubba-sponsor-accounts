<?php

namespace App\DTOs\InstitutionalRegistration;

use App\Enums\InstitutionalOrganizationType;
use App\Enums\NigeriaState;

final readonly class InstitutionalSponsorRegistrationData
{
    public function __construct(
        public string $organizationName,
        public InstitutionalOrganizationType $organizationType,
        public string $countryCode,
        public NigeriaState $state,
        public string $officialEmail,
        public string $officialPhone,
        public string $ownerName,
        public string $jobTitle,
        public string $ownerEmail,
        public string $ownerPhone,
        public string $password,
    ) {}
}
