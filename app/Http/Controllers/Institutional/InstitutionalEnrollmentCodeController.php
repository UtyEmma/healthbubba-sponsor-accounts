<?php

namespace App\Http\Controllers\Institutional;

use App\Http\Requests\Institutional\ViewEnrollmentCodesRequest;
use App\Http\Resources\InstitutionalEnrollmentCodePageResource;
use App\Queries\Institutional\InstitutionalEnrollmentCodeQuery;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InstitutionalEnrollmentCodeController
{
    public function __construct(private InstitutionalEnrollmentCodeQuery $codes) {}

    public function __invoke(ViewEnrollmentCodesRequest $request): Response
    {
        return Inertia::render('institutional-sponsor/enrollment-codes/index', [
            'enrollmentCodes' => new InstitutionalEnrollmentCodePageResource($this->codes->get($request->workspace())),
        ]);
    }
}
