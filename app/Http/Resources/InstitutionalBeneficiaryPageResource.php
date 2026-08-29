<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InstitutionalBeneficiaryPageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'beneficiaries' => InstitutionalBeneficiaryResource::collection($this->resource['beneficiaries']),
            'counts' => $this->resource['counts'],
            'campaigns' => $this->resource['campaigns']->map(static fn (Campaign $campaign): array => [
                'name' => $campaign->name,
                'slug' => $campaign->slug,
                'location' => $campaign->location,
                'endDate' => $campaign->end_date?->toDateString(),
                'estimatedBeneficiaries' => $campaign->estimated_beneficiaries,
                'beneficiaryLimit' => $campaign->beneficiary_limit,
                'ended' => $campaign->lifecycleStatus()->value === 'COMPLETED',
            ])->values(),
        ];
    }
}
