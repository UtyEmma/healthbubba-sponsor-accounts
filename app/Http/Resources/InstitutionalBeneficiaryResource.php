<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkspaceBeneficiary */
final class InstitutionalBeneficiaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $campaign = $this->relatable instanceof Campaign ? $this->relatable : null;

        return [
            'publicId' => $this->public_id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'name' => trim("{$this->first_name} {$this->last_name}"),
            'email' => $this->email,
            'phone' => $this->phone,
            'community' => $this->community,
            'status' => (string) $this->getAttribute('institutional_status'),
            'accessStatus' => $this->status->value,
            'source' => $this->source->value,
            'hasHealthBubbaAccount' => $this->beneficiary_id !== null,
            'campaign' => $campaign === null ? null : [
                'name' => $campaign->name,
                'slug' => $campaign->slug,
            ],
        ];
    }
}
