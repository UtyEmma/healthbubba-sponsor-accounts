<?php

namespace App\Http\Requests\InstitutionalCampaigns;

use App\Models\CampaignBeneficiaryImport;

final class DownloadCampaignImportErrorsRequest extends ManageInstitutionalCampaignRequest
{
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $import = $this->route('import');

        return $import instanceof CampaignBeneficiaryImport
            && $import->campaign_id === (int) $this->campaign()->getKey();
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [];
    }
}
