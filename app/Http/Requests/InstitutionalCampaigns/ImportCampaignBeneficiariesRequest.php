<?php

namespace App\Http\Requests\InstitutionalCampaigns;

use App\Enums\Account\Roles;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Http\Requests\InstitutionalOnboarding\AuthorizedInstitutionalWorkspaceRequest;
use App\Models\Campaign;
use App\Models\User;
use App\Models\WorkspaceMember;

final class ImportCampaignBeneficiariesRequest extends AuthorizedInstitutionalWorkspaceRequest
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
