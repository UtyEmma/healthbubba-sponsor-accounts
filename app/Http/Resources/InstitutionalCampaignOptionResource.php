<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Campaign */
final class InstitutionalCampaignOptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'location' => $this->location,
            'endDate' => $this->end_date?->toDateString(),
            'estimatedBeneficiaries' => $this->estimated_beneficiaries,
            'beneficiaryLimit' => $this->beneficiary_limit,
            'defaultLimit' => $this->beneficiary_limit ?? $this->estimated_beneficiaries ?? 1,
            'ended' => $this->lifecycleStatus()->value === 'COMPLETED',
        ];
    }
}
