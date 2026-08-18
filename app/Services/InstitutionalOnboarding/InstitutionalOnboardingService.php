<?php

namespace App\Services\InstitutionalOnboarding;

use App\Enums\AccountTypes;
use App\Models\Subscription;
use App\Models\Workspace;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final class InstitutionalOnboardingService
{
    public function requiresProfileCompletion(Workspace $workspace): bool {
        return $workspace->type === AccountTypes::INSTITUTION
            && $workspace->campaigns()->doesntExist();
    }

    public function hasProfileApproved(Workspace $workspace): bool
    {
        return $workspace->onboarded_at == null;
    }
}
