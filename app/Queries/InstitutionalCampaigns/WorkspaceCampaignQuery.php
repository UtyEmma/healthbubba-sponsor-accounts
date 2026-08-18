<?php

namespace App\Queries\InstitutionalCampaigns;

use App\Models\Campaign;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class WorkspaceCampaignQuery
{
    /** @return LengthAwarePaginator<int, Campaign> */
    public function paginate(Workspace $workspace): LengthAwarePaginator
    {
        return Campaign::query()
            ->whereBelongsTo($workspace)
            ->withCount(['beneficiaries', 'activeBeneficiaries'])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }
}
