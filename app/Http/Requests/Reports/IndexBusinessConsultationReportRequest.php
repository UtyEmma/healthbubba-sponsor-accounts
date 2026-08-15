<?php

namespace App\Http\Requests\Reports;

use App\Enums\AccountTypes;
use App\Http\Requests\WorkspaceMembers\AuthorizedWorkspaceViewRequest;

final class IndexBusinessConsultationReportRequest extends AuthorizedWorkspaceViewRequest
{
    public function authorize(): bool
    {
        return parent::authorize()
            && $this->workspace()->type === AccountTypes::BUSINESS;
    }
}
