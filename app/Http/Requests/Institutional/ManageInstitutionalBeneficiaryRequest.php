<?php

namespace App\Http\Requests\Institutional;

use App\DTOs\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryData;
use App\Http\Requests\InstitutionalOnboarding\AuthorizedInstitutionalWorkspaceRequest;
use App\Models\Campaign;
use Illuminate\Validation\Rule;

final class ManageInstitutionalBeneficiaryRequest extends AuthorizedInstitutionalWorkspaceRequest
{
    protected bool $manageOnly = true;

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'campaign' => ['required', 'string', Rule::exists('campaigns', 'slug')->where('workspace_id', $this->workspace()->getKey())],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'community' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'campaign' => $this->string('campaign')->trim()->toString(),
            'first_name' => $this->string('first_name')->squish()->toString(),
            'last_name' => $this->string('last_name')->squish()->toString(),
            'email' => $this->string('email')->trim()->lower()->toString(),
            'phone' => $this->string('phone')->squish()->toString(),
            'community' => $this->string('community')->squish()->toString(),
        ]);
    }

    public function campaign(): Campaign
    {
        return Campaign::query()
            ->whereBelongsTo($this->workspace())
            ->where('slug', $this->validated('campaign'))
            ->firstOrFail();
    }

    public function invitationData(): InviteWorkspaceBeneficiaryData
    {
        return InviteWorkspaceBeneficiaryData::fromArray($this->validated());
    }
}
