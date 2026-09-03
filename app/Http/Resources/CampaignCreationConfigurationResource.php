<?php

namespace App\Http\Resources;

use App\DTOs\Campaigns\CampaignCreationConfigurationData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CampaignCreationConfigurationData */
final class CampaignCreationConfigurationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'currency' => $this->currency,
            'walletBalance' => $this->walletBalance,
            'gpUnitFee' => $this->gpUnitFee,
            'specialistUnitFee' => $this->specialistUnitFee,
            'boothSetupUnitFee' => $this->boothSetupUnitFee,
            'boothMonthlyUnitFee' => $this->boothMonthlyUnitFee,
        ];
    }
}
