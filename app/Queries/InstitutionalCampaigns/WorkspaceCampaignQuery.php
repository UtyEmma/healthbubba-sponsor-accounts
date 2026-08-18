<?php

namespace App\Queries\InstitutionalCampaigns;

use App\Models\Campaign;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class WorkspaceCampaignQuery
{
    /** @return LengthAwarePaginator<int, Campaign> */
    public function paginate(Workspace $workspace): LengthAwarePaginator
    {
        return Campaign::query()
            ->whereBelongsTo($workspace)
            ->withCount([
                'beneficiaries',
                'activeBeneficiaries',
                'beneficiaries as capacity_used' => static function (Builder $query): void {
                    $query->consumingCapacity();
                },
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    /** @return LengthAwarePaginator<int, WorkspaceBeneficiary> */
    public function paginateBeneficiaries(Campaign $campaign): LengthAwarePaginator
    {
        return $campaign->beneficiaries()
            ->with('relatable')
            ->latest('id')
            ->paginate(
                perPage: 10,
                pageName: 'beneficiaries_page',
            )
            ->withQueryString();
    }

    public function prepareForDisplay(Campaign $campaign): Campaign
    {
        return $campaign->loadCount([
            'beneficiaries',
            'activeBeneficiaries',
            'beneficiaries as capacity_used' => static function (Builder $query): void {
                $query->consumingCapacity();
            },
        ]);
    }
}
