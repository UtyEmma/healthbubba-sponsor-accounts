<?php

namespace App\Queries\Institutional;

use App\Models\Campaign;
use App\Models\CampaignEnrollmentCode;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class InstitutionalEnrollmentCodeQuery
{
    /** @return array{codes: LengthAwarePaginator<int, CampaignEnrollmentCode>, campaigns: Collection<int, Campaign>} */
    public function get(Workspace $workspace): array
    {
        $codes = CampaignEnrollmentCode::query()
            ->whereIn('campaign_id', Campaign::query()->whereBelongsTo($workspace)->select('id'))
            ->with(['campaign' => function ($query): void {
                $query->withCount(['beneficiaries as enrolled_count' => function (Builder $query): void {
                    $query->consumingCapacity();
                }]);
            }])
            ->orderBy('expires_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return [
            'codes' => $codes,
            'campaigns' => Campaign::query()
                ->whereBelongsTo($workspace)
                ->whereNull('ended_at')
                ->where('end_date', '>=', today())
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'location', 'end_date', 'estimated_beneficiaries', 'beneficiary_limit']),
        ];
    }
}
