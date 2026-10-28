<?php

namespace App\Policies;

use App\Models\CampaignPricingTier;
use App\Models\User;

final class CampaignPricingTierPolicy
{
    public function viewAny(User $administrator): bool
    {
        return $administrator->isAdmin();
    }

    public function view(User $administrator, CampaignPricingTier $campaignPricingTier): bool
    {
        return $administrator->isAdmin();
    }

    public function create(User $administrator): bool
    {
        return $administrator->isAdmin();
    }

    public function update(User $administrator, CampaignPricingTier $campaignPricingTier): bool
    {
        return $administrator->isAdmin();
    }

    public function delete(User $administrator, CampaignPricingTier $campaignPricingTier): bool
    {
        return false;
    }

    public function deleteAny(User $administrator): bool
    {
        return false;
    }
}
