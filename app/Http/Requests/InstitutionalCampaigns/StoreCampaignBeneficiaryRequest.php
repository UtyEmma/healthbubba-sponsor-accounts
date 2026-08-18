<?php

namespace App\Http\Requests\InstitutionalCampaigns;

use App\DTOs\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryData;
use App\Enums\Account\Roles;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Http\Requests\InstitutionalOnboarding\AuthorizedInstitutionalWorkspaceRequest;
use App\Models\Campaign;
use App\Models\User;
use App\Models\WorkspaceMember;

final class StoreCampaignBeneficiaryRequest extends AuthorizedInstitutionalWorkspaceRequest
{
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $campaign = $this->route('campaign');
        $user = $this->user();

        if (! $campaign instanceof Campaign
            || ! $user instanceof User
            || $campaign->workspace_id !== (int) $this->workspace()->getKey()) {
            return false;
        }

        if ($user->role === Roles::SUPER_ADMIN) {
            return true;
        }

        return WorkspaceMember::query()
            ->whereBelongsTo($this->workspace())
            ->whereBelongsTo($user)
            ->where('status', WorkspaceMemberStatus::Active)
            ->whereIn('role', [
                WorkspaceMemberRole::Owner->value,
                WorkspaceMemberRole::Administrator->value,
            ])
            ->exists();
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => $this->string('first_name')->squish()->toString(),
            'last_name' => $this->string('last_name')->squish()->toString(),
            'email' => $this->string('email')->trim()->lower()->toString(),
            'phone' => $this->string('phone')->squish()->toString(),
        ]);
    }

    public function invitationData(): InviteWorkspaceBeneficiaryData
    {
        return InviteWorkspaceBeneficiaryData::fromArray($this->validated());
    }
}
