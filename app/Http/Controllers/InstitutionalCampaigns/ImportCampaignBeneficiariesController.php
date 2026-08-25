<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\WorkspaceBeneficiaries\ImportCampaignBeneficiariesAction;
use App\Http\Requests\InstitutionalCampaigns\ImportCampaignBeneficiariesRequest;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;

final readonly class ImportCampaignBeneficiariesController
{
    public function __construct(
        private ImportCampaignBeneficiariesAction $import,
    ) {}

    public function __invoke(
        ImportCampaignBeneficiariesRequest $request,
        Campaign $campaign,
    ): RedirectResponse {
        $file = $request->file('file');
        abort_if($file === null, 422);

        $result = $this->import->execute(
            $request->workspace(),
            $campaign,
            $request->onboardingUser(),
            $file,
        );

        return to_route('campaigns.show', $campaign)
            ->with('success', "Imported {$result->imported} beneficiary invitation(s).")
            ->with('import_result', $result->toArray());
    }
}
