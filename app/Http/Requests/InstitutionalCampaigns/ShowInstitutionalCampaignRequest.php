<?php

namespace App\Http\Requests\InstitutionalCampaigns;

use App\Http\Requests\InstitutionalOnboarding\AuthorizedInstitutionalWorkspaceRequest;
use App\Models\Campaign;

final class ShowInstitutionalCampaignRequest extends AuthorizedInstitutionalWorkspaceRequest
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
}
