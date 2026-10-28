<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

final class CampaignPolicy
{
    public function viewAny(User $administrator): bool
    {
        return $administrator->isAdmin();
    }

    public function view(User $administrator, Campaign $campaign): bool
    {
        return $administrator->isAdmin();
    }

    public function create(User $administrator): bool
    {
        return $administrator->isAdmin();
    }

    public function update(User $administrator, Campaign $campaign): bool
    {
        return $administrator->isAdmin();
    }

    public function delete(User $administrator, Campaign $campaign): bool
    {
        return false;
    }

    public function deleteAny(User $administrator): bool
    {
        return false;
    }
}
