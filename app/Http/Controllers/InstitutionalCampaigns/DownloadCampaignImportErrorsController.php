<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Http\Requests\InstitutionalCampaigns\DownloadCampaignImportErrorsRequest;
use App\Models\CampaignBeneficiaryImport;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadCampaignImportErrorsController
{
    public function __invoke(
        DownloadCampaignImportErrorsRequest $request,
        CampaignBeneficiaryImport $import,
    ): StreamedResponse {
        return response()->streamDownload(function () use ($import): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                return;
            }

            fputcsv($stream, ['Row', 'Identifier', 'Code', 'Message']);

            foreach ($import->errors as $error) {
                fputcsv($stream, [
                    $error['row'],
                    $error['identifier'] ?? '',
                    $error['code'],
                    $error['message'],
                ]);
            }

            fclose($stream);
        }, "campaign-import-{$import->public_id}-errors.csv", ['Content-Type' => 'text/csv']);
    }
}
