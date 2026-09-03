<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InstitutionalEnrollmentCodePageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'codes' => CampaignEnrollmentCodeResource::collection($this->resource['codes']),
            'campaigns' => $this->resource['campaigns']->map(static fn (Campaign $campaign): array => [
                'name' => $campaign->name,
                'slug' => $campaign->slug,
                'location' => $campaign->location,
                'endDate' => $campaign->end_date?->toDateString(),
                'defaultLimit' => $campaign->beneficiary_limit ?? $campaign->estimated_beneficiaries ?? 1,
            ])->values(),
        ];
    }
}
