<?php

namespace App\DTOs\Workspaces;

use App\Enums\AccountTypes;
use App\Enums\InstitutionalOrganizationType;
use App\Enums\NigeriaState;

final readonly class CreateWorkspaceData
{
    public function __construct(
        public string $name,
        public AccountTypes $accountType,
        public ?InstitutionalOrganizationType $organizationType = null,
        public ?string $countryCode = null,
        public ?NigeriaState $state = null,
        public ?string $officialEmail = null,
        public ?string $officialPhone = null,
        public ?string $memberPhone = null,
        public ?string $memberJobTitle = null,
        public bool $authorizationConfirmed = false,
    ) {}

    /** @return array<string, mixed> */
    public function workspaceAttributes(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->accountType,
            'organization_type' => $this->organizationType,
            'country_code' => $this->countryCode,
            'state_code' => $this->state,
            'official_email' => $this->officialEmail,
            'official_phone' => $this->officialPhone,
        ];
    }
}
