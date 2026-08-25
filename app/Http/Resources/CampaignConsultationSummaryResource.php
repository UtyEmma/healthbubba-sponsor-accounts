<?php

namespace App\Http\Resources;

use App\DTOs\Campaigns\CampaignConsultationSummaryData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CampaignConsultationSummaryData */
final class CampaignConsultationSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'coverage' => [
                'planName' => $this->campaignName,
                'activeBeneficiaries' => $this->activeBeneficiaries,
                'allocations' => $this->allocations,
                'scaling' => [
                    'available' => false,
                    'unavailableReason' => 'Campaign consultation coverage uses purchased units.',
                    'capacityLabel' => 'Campaign beneficiaries',
                    'capacityUnit' => 'beneficiary',
                    'capacityUnitPlural' => 'beneficiaries',
                    'includedCapacity' => null,
                    'currentCapacity' => null,
                    'maximumCapacity' => null,
                    'gpPerCapacity' => null,
                    'specialistPerCapacity' => null,
                    'description' => 'Campaign consultation coverage uses purchased units.',
                    'steps' => [],
                ],
            ],
            'financialSummary' => [
                'currency' => $this->currency,
                'walletBalance' => $this->walletBalance,
                'gpSpent' => $this->gpSpent,
                'specialistSpent' => $this->specialistSpent,
                'totalSpent' => $this->totalSpent,
            ],
        ];
    }
}
