<?php

namespace App\Http\Resources;

use App\DTOs\Dashboard\InstitutionalDashboard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InstitutionalDashboard */
final class InstitutionalDashboardResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'funding' => $this->funding,
            'beneficiaries' => $this->beneficiaries,
            'booths' => $this->booths,
            'campaignPerformance' => $this->campaignPerformance,
            'consultations' => $this->consultations,
            'consultationTrends' => $this->consultationTrends,
            'activities' => $this->activities,
            'remainingCampaigns' => $this->remainingCampaigns,
        ];
    }
}
