<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InstitutionalReportsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'byCampaign' => $this->resource['byCampaign'],
            'community' => $this->resource['community'],
            'impact' => $this->resource['impact'],
            'exports' => [
                ['type' => 'beneficiaries', 'title' => 'Beneficiary Report', 'description' => 'Roster, statuses, communities', 'available' => true],
                ['type' => 'coverage', 'title' => 'Coverage Report', 'description' => 'Allocated, utilized, remaining', 'available' => true],
                ['type' => 'utilization', 'title' => 'Utilization Report', 'description' => 'Healthcare usage over time', 'available' => true],
                ['type' => 'referrals', 'title' => 'Referral Report', 'description' => 'Referral cases & outcomes', 'available' => false],
            ],
        ];
    }
}
