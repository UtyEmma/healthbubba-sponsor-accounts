<?php

namespace App\Http\Resources;

use App\DTOs\WorkspacePlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkspacePlan $plan */
        $plan = $this->resource;

        return $plan->toArray();
    }
}
