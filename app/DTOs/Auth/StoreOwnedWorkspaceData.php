<?php

namespace App\DTOs\Auth;

use App\DTOs\Workspaces\CreateWorkspaceData;
use App\Enums\AccountTypes;
use App\Enums\InstitutionalOrganizationType;
use App\Enums\NigeriaState;

final readonly class StoreOwnedWorkspaceData
{
    public function __construct(
        public AccountTypes $accountType,
        public ?string $organizationName,
        public ?InstitutionalOrganizationType $organizationType,
        public ?string $countryCode,
        public ?NigeriaState $state,
        public ?string $officialEmail,
        public ?string $officialPhone,
        public bool $authorizationConfirmed,
    ) {}

    public function workspaceData(string $ownerName, ?string $ownerPhone): CreateWorkspaceData
    {
        return new CreateWorkspaceData(
            name: $this->accountType === AccountTypes::INDIVIDUAL
                ? "{$ownerName}'s Workspace"
                : (string) $this->organizationName,
            accountType: $this->accountType,
            organizationType: $this->organizationType,
            countryCode: $this->countryCode,
            state: $this->state,
            officialEmail: $this->officialEmail,
            officialPhone: $this->officialPhone,
            memberPhone: $ownerPhone,
            authorizationConfirmed: $this->authorizationConfirmed,
        );
    }
}
