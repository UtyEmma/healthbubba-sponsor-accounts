<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\WorkspaceBeneficiaries\ImportCampaignBeneficiariesAction;
use App\Http\Requests\InstitutionalCampaigns\ImportCampaignBeneficiariesRequest;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

final readonly class ImportCampaignBeneficiariesController
{
    public function __construct(
        private ImportCampaignBeneficiariesAction $import,
    ) {}

    public function __invoke(
        ImportCampaignBeneficiariesRequest $request,
        Campaign $campaign,
    ): RedirectResponse {
        $uploaded = $request->file('file');
        $source = $uploaded instanceof UploadedFile
            ? $uploaded
            : (string) $request->validated('rows');

        $result = $this->import->execute(
            $request->workspace(),
            $campaign,
            $request->onboardingUser(),
            $source,
        );

        return to_route('campaigns.show', $campaign)
            ->with('success', "Enrolled {$result->imported} beneficiary record(s).")
            ->with('import_result', $result->toArray());
    }
}
