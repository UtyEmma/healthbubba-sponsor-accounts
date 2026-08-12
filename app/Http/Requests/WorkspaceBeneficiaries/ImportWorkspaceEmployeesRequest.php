<?php

namespace App\Http\Requests\WorkspaceBeneficiaries;

use App\Enums\AccountTypes;

final class ImportWorkspaceEmployeesRequest extends AuthorizedWorkspaceBeneficiaryRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->workspace()->type === AccountTypes::BUSINESS;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:csv,xlsx',
                'mimetypes:text/csv,text/plain,application/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip',
            ],
        ];
    }
}
