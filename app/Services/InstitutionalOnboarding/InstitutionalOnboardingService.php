<?php

namespace App\Services\InstitutionalOnboarding;

use App\Enums\AccountTypes;
use App\Models\Workspace;

final class InstitutionalOnboardingService
{
    public function requiresProfileCompletion(Workspace $workspace): bool
    {
        return $workspace->type === AccountTypes::INSTITUTION
            && $workspace->campaigns()->doesntExist();
    }
}
