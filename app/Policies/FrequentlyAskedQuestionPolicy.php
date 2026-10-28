<?php

namespace App\Policies;

use App\Models\FrequentlyAskedQuestion;
use App\Models\User;

final class FrequentlyAskedQuestionPolicy
{
    public function viewAny(User $administrator): bool
    {
        return $administrator->isAdmin();
    }

    public function view(User $administrator, FrequentlyAskedQuestion $frequentlyAskedQuestion): bool
    {
        return $administrator->isAdmin();
    }

    public function create(User $administrator): bool
    {
        return $administrator->isAdmin();
    }

    public function update(User $administrator, FrequentlyAskedQuestion $frequentlyAskedQuestion): bool
    {
        return $administrator->isAdmin();
    }

    public function delete(User $administrator, FrequentlyAskedQuestion $frequentlyAskedQuestion): bool
    {
        return $administrator->isAdmin();
    }

    public function deleteAny(User $administrator): bool
    {
        return false;
    }
}
