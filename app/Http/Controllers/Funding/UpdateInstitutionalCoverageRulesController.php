<?php

namespace App\Http\Controllers\Funding;

use App\Actions\Funding\UpdateInstitutionalCoverageRulesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Funding\UpdateInstitutionalCoverageRulesRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateInstitutionalCoverageRulesController extends Controller
{
    public function __invoke(
        UpdateInstitutionalCoverageRulesRequest $request,
        UpdateInstitutionalCoverageRulesAction $action,
    ): RedirectResponse {
        $action->execute($request->toData());

        return back()->with('success', 'Coverage rules updated.');
    }
}
