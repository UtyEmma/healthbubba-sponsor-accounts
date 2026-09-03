<?php

namespace App\Http\Controllers\Institutional;

use App\Actions\WorkspaceBeneficiaries\ImportCampaignBeneficiariesAction;
use App\Http\Requests\Institutional\ImportInstitutionalBeneficiariesRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

final readonly class ImportInstitutionalBeneficiariesController
{
    public function __construct(private ImportCampaignBeneficiariesAction $import) {}

    public function __invoke(ImportInstitutionalBeneficiariesRequest $request): RedirectResponse
    {
        $uploaded = $request->file('file');
        $result = $this->import->execute(
            $request->workspace(),
            $request->campaign(),
            $request->onboardingUser(),
            $uploaded instanceof UploadedFile ? $uploaded : (string) $request->validated('rows'),
        );

        return back()
            ->with('success', "Enrolled {$result->imported} beneficiary record(s).")
            ->with('import_result', [
                ...$result->toArray(),
                'campaignSlug' => $request->campaign()->slug,
            ]);
    }
}
