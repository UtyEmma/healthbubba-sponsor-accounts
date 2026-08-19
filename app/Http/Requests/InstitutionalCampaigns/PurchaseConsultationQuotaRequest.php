<?php

namespace App\Http\Requests\InstitutionalCampaigns;

use App\Enums\Consultations\ConsultationType;
use App\Http\Requests\InstitutionalOnboarding\AuthorizedInstitutionalWorkspaceRequest;
use App\Models\Campaign;
use Illuminate\Validation\Rule;

final class PurchaseConsultationQuotaRequest extends AuthorizedInstitutionalWorkspaceRequest
{
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $campaign = $this->route('campaign');

        return $campaign instanceof Campaign
            && $campaign->workspace_id === (int) $this->workspace()->getKey();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'consultation_type' => ['required', Rule::enum(ConsultationType::class)],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
