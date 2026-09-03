<?php

namespace App\Http\Requests\Institutional;

use App\Http\Requests\InstitutionalOnboarding\AuthorizedInstitutionalWorkspaceRequest;
use App\Models\Campaign;
use Illuminate\Validation\Rule;

final class ImportInstitutionalBeneficiariesRequest extends AuthorizedInstitutionalWorkspaceRequest
{
    protected bool $manageOnly = true;

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'campaign' => ['required', 'string', Rule::exists('campaigns', 'slug')->where('workspace_id', $this->workspace()->getKey())],
            'file' => [
                Rule::requiredIf(! filled($this->input('rows'))),
                'nullable', 'file', 'max:10240', 'mimes:csv,xlsx',
                'mimetypes:text/csv,text/plain,application/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip',
            ],
            'rows' => [Rule::requiredIf(! $this->hasFile('file')), 'nullable', 'string', 'max:1000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'campaign' => $this->string('campaign')->trim()->toString(),
            'rows' => is_string($this->input('rows')) ? trim((string) $this->input('rows')) : $this->input('rows'),
        ]);
    }

    public function campaign(): Campaign
    {
        return Campaign::query()
            ->whereBelongsTo($this->workspace())
            ->where('slug', $this->validated('campaign'))
            ->firstOrFail();
    }
}
