<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CampaignDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'capabilities' => $this->resource['capabilities'],
            'counts' => $this->resource['counts'],
            'configuration' => $this->resource['configuration'],
            'enrollmentCode' => $this->resource['enrollmentCode'],
            'booths' => CampaignBoothResource::collection($this->resource['booths']),
            'ledger' => $this->resource['ledger'],
        ];
    }
}
