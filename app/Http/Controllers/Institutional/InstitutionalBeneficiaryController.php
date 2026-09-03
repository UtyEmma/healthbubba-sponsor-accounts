<?php

namespace App\Http\Controllers\Institutional;

use App\Enums\InstitutionalBeneficiaryStatus;
use App\Http\Requests\Institutional\ViewInstitutionalBeneficiariesRequest;
use App\Http\Resources\InstitutionalBeneficiaryPageResource;
use App\Queries\Institutional\InstitutionalBeneficiaryQuery;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InstitutionalBeneficiaryController
{
    public function __construct(private InstitutionalBeneficiaryQuery $beneficiaries) {}

    public function __invoke(ViewInstitutionalBeneficiariesRequest $request): Response
    {
        $status = filled($request->validated('status'))
            ? InstitutionalBeneficiaryStatus::from((string) $request->validated('status'))
            : null;

        return Inertia::render('institutional-sponsor/beneficiaries/index', [
            'roster' => new InstitutionalBeneficiaryPageResource($this->beneficiaries->get(
                $request->workspace(),
                $request->string('search')->trim()->value() ?: null,
                $request->string('campaign')->trim()->value() ?: null,
                $status,
            )),
            'filters' => $request->safe()->only(['search', 'campaign', 'status']),
            'importResult' => $request->session()->get('import_result'),
        ]);
    }
}
