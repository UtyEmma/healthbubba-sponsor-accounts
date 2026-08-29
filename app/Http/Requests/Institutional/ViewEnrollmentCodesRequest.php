<?php

namespace App\Http\Requests\Institutional;

use App\Http\Requests\InstitutionalOnboarding\AuthorizedInstitutionalWorkspaceRequest;

final class ViewEnrollmentCodesRequest extends AuthorizedInstitutionalWorkspaceRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['page' => ['nullable', 'integer', 'min:1']];
    }
}
