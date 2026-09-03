<?php

namespace App\Http\Resources;

use App\Models\CampaignEnrollmentCode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CampaignEnrollmentCode */
final class CampaignEnrollmentCodeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $enrolled = (int) $this->campaign->getAttribute('enrolled_count');
        $expired = $this->expires_at->isBefore(today());
        $full = $enrolled >= $this->enrollment_limit;

        return [
            'id' => $this->public_id,
            'code' => $this->code,
            'enrollmentLimit' => $this->enrollment_limit,
            'enrolled' => $enrolled,
            'expiresAt' => $this->expires_at->toDateString(),
            'status' => $expired ? 'expired' : ($full ? 'full' : 'active'),
            'statusLabel' => $expired ? 'Expired' : ($full ? 'Full' : 'Active'),
            'campaign' => [
                'name' => $this->campaign->name,
                'slug' => $this->campaign->slug,
                'location' => $this->campaign->location,
            ],
        ];
    }
}
