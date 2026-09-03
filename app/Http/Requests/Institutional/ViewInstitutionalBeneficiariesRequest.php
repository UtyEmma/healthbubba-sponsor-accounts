<?php

namespace App\Http\Requests\Institutional;

use App\Enums\InstitutionalBeneficiaryStatus;
use App\Http\Requests\InstitutionalOnboarding\AuthorizedInstitutionalWorkspaceRequest;
use Illuminate\Validation\Rule;

final class ViewInstitutionalBeneficiariesRequest extends AuthorizedInstitutionalWorkspaceRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(InstitutionalBeneficiaryStatus::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
