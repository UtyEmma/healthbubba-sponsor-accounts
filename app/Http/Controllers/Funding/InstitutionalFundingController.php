<?php

namespace App\Http\Controllers\Funding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Funding\ViewInstitutionalFundingRequest;
use App\Http\Resources\InstitutionalFundingResource;
use App\Queries\Funding\InstitutionalFundingQuery;
use Inertia\Inertia;
use Inertia\Response;

final class InstitutionalFundingController extends Controller
{
    public function __invoke(
        ViewInstitutionalFundingRequest $request,
        InstitutionalFundingQuery $query,
    ): Response {
        return Inertia::render('funding/index', [
            'funding' => new InstitutionalFundingResource($query->get($request->workspace())),
        ]);
    }
}
