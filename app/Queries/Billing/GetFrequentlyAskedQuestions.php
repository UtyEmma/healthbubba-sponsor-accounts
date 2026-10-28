<?php

namespace App\Queries\Billing;

use App\Enums\AccountTypes;
use App\Models\FrequentlyAskedQuestion;
use Illuminate\Database\Eloquent\Collection;

final readonly class GetFrequentlyAskedQuestions
{
    /** @return Collection<int, FrequentlyAskedQuestion> */
    public function execute(AccountTypes $accountType): Collection
    {
        return FrequentlyAskedQuestion::query()
            ->visibleFor($accountType)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
