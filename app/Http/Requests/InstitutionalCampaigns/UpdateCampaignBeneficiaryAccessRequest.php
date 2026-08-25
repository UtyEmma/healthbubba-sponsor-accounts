<?php

namespace App\Http\Requests\InstitutionalCampaigns;

use App\Enums\Account\Roles;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryAccessAction;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Http\Requests\InstitutionalOnboarding\AuthorizedInstitutionalWorkspaceRequest;
use App\Models\Campaign;
use App\Models\User;
use App\Models\WorkspaceBeneficiary;
use App\Models\WorkspaceMember;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UpdateCampaignBeneficiaryAccessRequest extends AuthorizedInstitutionalWorkspaceRequest
{
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $campaign = $this->route('campaign');
        $beneficiary = $this->route('workspaceBeneficiary');
        $user = $this->user();

        if (! $campaign instanceof Campaign
            || ! $beneficiary instanceof WorkspaceBeneficiary
            || ! $user instanceof User
            || $campaign->workspace_id !== (int) $this->workspace()->getKey()
            || $beneficiary->workspace_id !== (int) $this->workspace()->getKey()
            || $beneficiary->relatable_type !== $campaign->getMorphClass()
            || $beneficiary->relatable_id !== $campaign->getKey()) {
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

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(WorkspaceBeneficiaryAccessAction::class)],
        ];
    }

    public function beneficiary(): WorkspaceBeneficiary
    {
        $beneficiary = $this->route('workspaceBeneficiary');

        return $beneficiary instanceof WorkspaceBeneficiary
            ? $beneficiary
            : throw new NotFoundHttpException('Campaign beneficiary not found.');
    }

    public function accessAction(): WorkspaceBeneficiaryAccessAction
    {
        return WorkspaceBeneficiaryAccessAction::from($this->string('action')->toString());
    }
}
