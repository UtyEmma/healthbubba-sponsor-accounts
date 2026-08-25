<?php

namespace App\Actions\WorkspaceBeneficiaries;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\DTOs\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryData;
use App\Enums\AccountTypes;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Services\WorkspaceBeneficiaries\BeneficiaryLookupService;
use App\Services\WorkspaceBeneficiaries\CampaignBeneficiaryCapacityService;
use App\Services\WorkspaceBeneficiaries\EmployeeIdGenerator;
use App\Services\WorkspaceBeneficiaries\WorkspaceBeneficiaryCapacityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class InviteWorkspaceBeneficiaryAction
{
    public function __construct(
        private BeneficiaryLookupService $beneficiaries,
        private WorkspaceBeneficiaryCapacityService $capacity,
        private CampaignBeneficiaryCapacityService $campaignCapacity,
        private EmployeeIdGenerator $employeeIds,
        private SendWorkspaceBeneficiaryInvitationAction $sendInvitation,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(
        Workspace $workspace,
        User $inviter,
        InviteWorkspaceBeneficiaryData $data,
        Workspace|Campaign $relatable,
    ): WorkspaceBeneficiary {
        $beneficiaryId = $this->beneficiaries->idsByEmail([$data->email])->get($data->email);

        $invitation = DB::transaction(function () use ($workspace, $inviter, $data, $beneficiaryId, $relatable): WorkspaceBeneficiary {
            $target = $this->lockTarget($workspace, $relatable);

            if ($target instanceof Campaign) {
                $this->campaignCapacity->expirePending($target);
                $capacityExhausted = $this->campaignCapacity->used($target) >= $target->beneficiary_limit;
            } else {
                $subscription = $this->capacity->lockSubscription($workspace);
                $this->capacity->expirePending($workspace);
                $capacityExhausted = $this->capacity->used($workspace) >= $subscription->capacity_count;
            }

            $existing = WorkspaceBeneficiary::query()
                ->whereBelongsTo($workspace)
                ->where('relatable_type', $target->getMorphClass())
                ->where('relatable_id', $target->getKey())
                ->where('email', $data->email)
                ->lockForUpdate()
                ->first();

            if (in_array($existing?->status, [
                WorkspaceBeneficiaryStatus::Active,
                WorkspaceBeneficiaryStatus::Suspended,
                WorkspaceBeneficiaryStatus::Pending,
            ], true)) {
                throw ValidationException::withMessages([
                    'email' => $target instanceof Campaign
                        ? 'This email already has an active, suspended, or pending record for this campaign.'
                        : 'This email already has an active, suspended, or pending workspace record.',
                ]);
            }

            if ($capacityExhausted) {
                throw ValidationException::withMessages([
                    'capacity' => $target instanceof Campaign
                        ? 'This campaign has reached its beneficiary limit.'
                        : 'There are no remaining beneficiary or employee seats on the current plan.',
                ]);
            }

            $employeeId = $workspace->type === AccountTypes::BUSINESS
                ? ($data->employeeId ?? $this->employeeIds->generate($workspace))
                : null;

            if ($employeeId !== null && $workspace->workspaceBeneficiaries()
                ->where('employee_id', $employeeId)
                ->when($existing, fn ($query) => $query->whereKeyNot($existing->getKey()))
                ->exists()) {
                throw ValidationException::withMessages([
                    'employee_id' => 'This employee ID is already in use in the workspace.',
                ]);
            }

            $now = now();
            $invitation = $existing ?? new WorkspaceBeneficiary([
                'workspace_id' => $workspace->getKey(),
                'public_id' => (string) Str::ulid(),
            ]);
            $invitation->relatable()->associate($target);

            $invitation->fill([
                'invited_by_user_id' => $inviter->getKey(),
                'beneficiary_id' => $beneficiaryId,
                'first_name' => $data->firstName,
                'last_name' => $data->lastName,
                'email' => $data->email,
                'phone' => $data->phone,
                'department' => $workspace->type === AccountTypes::BUSINESS ? $data->department : null,
                'employee_id' => $employeeId,
                'status' => WorkspaceBeneficiaryStatus::Pending,
                'source' => $data->source,
                'invitation_version' => $existing === null ? 1 : $existing->invitation_version + 1,
                'invited_at' => $now,
                'expires_at' => $now->copy()->addDay(),
                'accepted_at' => null,
                'declined_at' => null,
                'cancelled_at' => null,
                'suspended_at' => null,
                'revoked_at' => null,
            ])->save();

            $label = $workspace->type === AccountTypes::BUSINESS ? 'employee' : 'beneficiary';
            $name = trim("{$invitation->first_name} {$invitation->last_name}");
            $this->activities->record($workspace, new WorkspaceActivityData(
                type: WorkspaceActivityType::BeneficiaryInvited,
                title: "Invited {$label} {$name}",
                actor: WorkspaceActivityActor::user($inviter),
                subjectType: 'workspace_beneficiary',
                subjectId: $invitation->public_id,
                subjectName: $name,
            ));

            return $invitation;
        });

        $this->sendInvitation->execute($invitation);

        return $invitation;
    }

    private function lockTarget(Workspace $workspace, Workspace|Campaign $relatable): Workspace|Campaign
    {
        if ($relatable instanceof Campaign) {
            if ($workspace->type !== AccountTypes::INSTITUTION) {
                throw ValidationException::withMessages([
                    'campaign' => 'Campaign beneficiaries are only available to institutional workspaces.',
                ]);
            }

            return $this->campaignCapacity->lockCampaign($workspace, $relatable);
        }

        if (! $relatable->is($workspace)
            || ! in_array($workspace->type, [AccountTypes::INDIVIDUAL, AccountTypes::BUSINESS], true)) {
            throw ValidationException::withMessages([
                'workspace' => 'The selected beneficiary target is not available for this workspace.',
            ]);
        }

        return $workspace;
    }
}
