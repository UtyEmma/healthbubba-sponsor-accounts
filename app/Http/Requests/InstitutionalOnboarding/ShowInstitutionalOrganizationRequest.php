<?php

namespace App\Http\Requests\InstitutionalOnboarding;

final class ShowInstitutionalOrganizationRequest extends AuthorizedInstitutionalWorkspaceRequest
{
    protected bool $ownerOnly = true;
}
