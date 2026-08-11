<?php

namespace App\Http\Resources;

use App\DTOs\CapacityPurchases\CapacityPurchaseSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CapacityPurchaseSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var CapacityPurchaseSummary $summary */
        $summary = $this->resource;

        return $summary->toArray();
    }
}
