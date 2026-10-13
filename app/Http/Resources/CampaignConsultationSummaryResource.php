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
                    'scheduledPerCapacity' => null,
                    'instantPerCapacity' => null,
                    'description' => 'Campaign consultation coverage uses purchased units.',
                    'steps' => [],
                ],
            ],
            'financialSummary' => [
                'currency' => $this->currency,
                'walletBalance' => $this->walletBalance,
                'scheduledSpent' => $this->scheduledSpent,
                'instantSpent' => $this->instantSpent,
                'totalSpent' => $this->totalSpent,
            ],
        ];
    }
}
