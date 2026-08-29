<?php

namespace App\Http\Controllers\Institutional;

use App\Http\Requests\Institutional\ExportInstitutionalReportRequest;
use App\Services\Reports\InstitutionalReportExporter;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ExportInstitutionalReportController
{
    public function __construct(private InstitutionalReportExporter $exporter) {}

    public function __invoke(ExportInstitutionalReportRequest $request): BinaryFileResponse|StreamedResponse|View
    {
        return $this->exporter->export(
            $request->workspace(),
            $request->reportType(),
            $request->reportFormat(),
        );
    }
}
