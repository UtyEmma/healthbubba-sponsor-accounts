<?php

namespace App\Http\Controllers\Institutional;

use App\Actions\EnrollmentCodes\CreateCampaignEnrollmentCodeAction;
use App\Http\Requests\Institutional\StoreEnrollmentCodeRequest;
use Illuminate\Http\RedirectResponse;

final readonly class StoreEnrollmentCodeController
{
    public function __construct(private CreateCampaignEnrollmentCodeAction $create) {}

    public function __invoke(StoreEnrollmentCodeRequest $request): RedirectResponse
    {
        $this->create->execute($request->enrollmentCodeData());

        return back()->with('success', 'Enrollment code created successfully.');
    }
}
