<?php

namespace App\Http\Requests\InstitutionalCampaigns;

use App\Models\CampaignBooth;

final class ManageCampaignBoothRequest extends ManageInstitutionalCampaignRequest
{
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $booth = $this->route('booth');

        return $booth instanceof CampaignBooth
            && $booth->campaign_id === (int) $this->campaign()->getKey();
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [];
    }

    public function booth(): CampaignBooth
    {
        /** @var CampaignBooth $booth */
        $booth = $this->route('booth');

        return $booth;
    }
}
