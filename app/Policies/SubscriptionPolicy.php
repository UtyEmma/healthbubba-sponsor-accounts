<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

final class SubscriptionPolicy
{
    public function viewAny(User $administrator): bool
    {
        return $administrator->isAdmin();
    }

    public function view(User $administrator, Subscription $subscription): bool
    {
        return $administrator->isAdmin();
    }

    public function create(User $administrator): bool
    {
        return $administrator->isAdmin();
    }

    public function update(User $administrator, Subscription $subscription): bool
    {
        return false;
    }

    public function delete(User $administrator, Subscription $subscription): bool
    {
        return false;
    }

    public function deleteAny(User $administrator): bool
    {
        return false;
    }
}
