<?php

namespace App\Http\Resources;

use App\Models\Workspace;
use App\Repositories\FeaturesRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $workspace = Workspace::current();

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'features' => (new FeaturesRepository)->getPlanFeatures($this->resource),
            'is_current' => $workspace->onPlan($this->resource),
        ];
    }
}
