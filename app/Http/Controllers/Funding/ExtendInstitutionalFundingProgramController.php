<?php

namespace App\Http\Controllers\Funding;

use App\Actions\Funding\ExtendInstitutionalFundingProgramAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Funding\ExtendInstitutionalFundingProgramRequest;
use Illuminate\Http\RedirectResponse;

final class ExtendInstitutionalFundingProgramController extends Controller
{
    public function __invoke(
        ExtendInstitutionalFundingProgramRequest $request,
        ExtendInstitutionalFundingProgramAction $action,
    ): RedirectResponse {
        $action->execute($request->toData());

        return back()->with('success', 'Program end date extended.');
    }
}
