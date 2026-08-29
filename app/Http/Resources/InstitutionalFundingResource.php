<?php

namespace App\Http\Resources;

use App\DTOs\Funding\InstitutionalFundingPageData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InstitutionalFundingPageData */
final class InstitutionalFundingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'summary' => $this->summary,
            'program' => $this->program,
            'campaigns' => $this->campaigns,
            'returnedFromEndedCampaigns' => $this->returnedFromEndedCampaigns,
            'transactions' => $this->transactions,
            'transactionCount' => $this->transactionCount,
            'configuration' => $this->configuration,
        ];
    }
}
