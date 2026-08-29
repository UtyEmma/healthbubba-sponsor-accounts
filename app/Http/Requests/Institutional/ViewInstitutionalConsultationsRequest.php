<?php

namespace App\Http\Requests\Institutional;

use App\Http\Requests\InstitutionalOnboarding\AuthorizedInstitutionalWorkspaceRequest;
use Illuminate\Validation\Rule;

final class ViewInstitutionalConsultationsRequest extends AuthorizedInstitutionalWorkspaceRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'campaign' => ['nullable', 'string', Rule::exists('campaigns', 'slug')->where('workspace_id', $this->workspace()->getKey())],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
