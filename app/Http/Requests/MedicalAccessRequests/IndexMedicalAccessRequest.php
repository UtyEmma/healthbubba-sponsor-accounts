<?php

namespace App\Http\Requests\MedicalAccessRequests;

use App\Enums\AccountTypes;
use App\Http\Requests\WorkspaceMembers\AuthorizedWorkspaceViewRequest;

final class IndexMedicalAccessRequest extends AuthorizedWorkspaceViewRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->workspace()->type === AccountTypes::INDIVIDUAL;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
