<?php

namespace App\Http\Controllers\Institutional;

use App\Http\Requests\Institutional\ViewInstitutionalReportsRequest;
use App\Http\Resources\InstitutionalReportsResource;
use App\Queries\Institutional\InstitutionalReportsQuery;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InstitutionalReportsController
{
    public function __construct(private InstitutionalReportsQuery $reports) {}

    public function __invoke(ViewInstitutionalReportsRequest $request): Response
    {
        return Inertia::render('institutional-sponsor/reports/index', [
            'reports' => new InstitutionalReportsResource($this->reports->get($request->workspace())),
        ]);
    }
}
