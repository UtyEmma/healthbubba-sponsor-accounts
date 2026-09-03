<?php

namespace App\Services\Reports;

use App\Enums\InstitutionalReportFormat;
use App\Enums\InstitutionalReportType;
use App\Models\Workspace;
use App\Queries\Institutional\InstitutionalReportsQuery;
use Illuminate\Contracts\View\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class InstitutionalReportExporter
{
    public function __construct(private InstitutionalReportsQuery $reports) {}

    public function export(Workspace $workspace, InstitutionalReportType $type, InstitutionalReportFormat $format): BinaryFileResponse|StreamedResponse|View
    {
        $dataset = $this->reports->export($workspace, $type);
        $filename = $type->value.'-report-'.now()->format('Y-m-d');

        return match ($format) {
            InstitutionalReportFormat::Csv => $this->csv($dataset, $filename),
            InstitutionalReportFormat::Xlsx => $this->xlsx($dataset, $filename),
            InstitutionalReportFormat::Print => view('reports.institutional-print', [
                'workspace' => $workspace,
                'report' => $dataset,
            ]),
        };
    }

    /** @param array{headers: list<string>, rows: array<int, array<int, string|int|float|null>>} $dataset */
    private function csv(array $dataset, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($dataset): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                return;
            }
            fputcsv($stream, $dataset['headers']);
            foreach ($dataset['rows'] as $row) {
                fputcsv($stream, $row);
            }
            fclose($stream);
        }, $filename.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param array{headers: list<string>, rows: array<int, array<int, string|int|float|null>>} $dataset */
    private function xlsx(array $dataset, string $filename): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'institutional-report-');
        abort_if($path === false, 500, 'The report file could not be created.');
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($dataset['headers']));
        foreach ($dataset['rows'] as $row) {
            $writer->addRow(Row::fromValues(array_values($row)));
        }
        $writer->close();

        return response()->download($path, $filename.'.xlsx')->deleteFileAfterSend();
    }
}
