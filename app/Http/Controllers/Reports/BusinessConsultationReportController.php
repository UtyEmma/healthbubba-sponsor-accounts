<?php

namespace App\Http\Controllers\Reports;

use App\Http\Requests\Reports\IndexBusinessConsultationReportRequest;
use App\Http\Resources\BusinessConsultationReportResource;
use App\Queries\Reports\BusinessConsultationReportQuery;
use Inertia\Inertia;
use Inertia\Response;

final readonly class BusinessConsultationReportController
{
    public function __construct(
        private BusinessConsultationReportQuery $report,
    ) {}

    public function __invoke(IndexBusinessConsultationReportRequest $request): Response
    {
        return Inertia::render('business-sponsor/consultations/index', [
            'report' => new BusinessConsultationReportResource(
                $this->report->execute($request->workspace()),
            ),
        ]);
    }
}
