<?php

namespace App\Http\Resources;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Workspace */
final class WorkspaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'name' => $this->name,
            'logo' => $this->logo,
            'description' => $this->description,
            'onboardedAt' => $this->onboarded_at?->toISOString(),
            'type' => $this->type->value,
            'organizationType' => $this->organization_type?->value,
            'organizationTypeLabel' => $this->organization_type?->label(),
            'countryCode' => $this->country_code,
            'stateCode' => $this->state_code?->value,
            'stateLabel' => $this->state_code?->label(),
            'officialEmail' => $this->official_email,
            'officialPhone' => $this->official_phone,
            'fallbackChannel' => $this->fallback_channel?->value,
        ];
    }
}
