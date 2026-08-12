<?php

namespace App\Actions\WorkspaceBeneficiaries;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\DTOs\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryData;
use App\Enums\AccountTypes;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Services\WorkspaceBeneficiaries\BeneficiaryLookupService;
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
        private EmployeeIdGenerator $employeeIds,
        private SendWorkspaceBeneficiaryInvitationAction $sendInvitation,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(Workspace $workspace, User $inviter, InviteWorkspaceBeneficiaryData $data): WorkspaceBeneficiary
    {
        $beneficiaryId = $this->beneficiaries->idsByEmail([$data->email])->get($data->email);

        $invitation = DB::transaction(function () use ($workspace, $inviter, $data, $beneficiaryId): WorkspaceBeneficiary {
            $subscription = $this->capacity->lockSubscription($workspace);
            $this->capacity->expirePending($workspace);

            $existing = $workspace->workspaceBeneficiaries()
                ->where('email', $data->email)
                ->lockForUpdate()
                ->first();

            if (in_array($existing?->status, [
                WorkspaceBeneficiaryStatus::Active,
                WorkspaceBeneficiaryStatus::Suspended,
                WorkspaceBeneficiaryStatus::Pending,
            ], true)) {
                throw ValidationException::withMessages([
                    'email' => 'This email already has an active, suspended, or pending workspace record.',
                ]);
            }

            if ($this->capacity->used($workspace) >= $subscription->capacity_count) {
                throw ValidationException::withMessages([
                    'capacity' => 'There are no remaining beneficiary or employee seats on the current plan.',
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
}
