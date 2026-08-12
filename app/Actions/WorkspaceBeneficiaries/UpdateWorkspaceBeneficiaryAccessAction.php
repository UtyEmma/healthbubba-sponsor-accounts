<?php

namespace App\Actions\WorkspaceBeneficiaries;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryAccessAction;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Services\WorkspaceBeneficiaries\WorkspaceBeneficiaryCapacityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateWorkspaceBeneficiaryAccessAction
{
    public function __construct(
        private WorkspaceBeneficiaryCapacityService $capacity,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(
        Workspace $workspace,
        User $user,
        WorkspaceBeneficiary $beneficiary,
        WorkspaceBeneficiaryAccessAction $action,
    ): WorkspaceBeneficiary {
        return DB::transaction(function () use ($workspace, $user, $beneficiary, $action): WorkspaceBeneficiary {
            $this->capacity->lockSubscription($workspace);
            $locked = $workspace->workspaceBeneficiaries()
                ->whereKey($beneficiary->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $updated = match ($action) {
                WorkspaceBeneficiaryAccessAction::Suspend => $this->suspend($locked),
                WorkspaceBeneficiaryAccessAction::Restore => $this->restore($locked),
                WorkspaceBeneficiaryAccessAction::Revoke => $this->revoke($locked),
            };

            $name = trim("{$updated->first_name} {$updated->last_name}");
            [$type, $verb] = match ($action) {
                WorkspaceBeneficiaryAccessAction::Suspend => [WorkspaceActivityType::BeneficiarySuspended, 'Suspended access for'],
                WorkspaceBeneficiaryAccessAction::Restore => [WorkspaceActivityType::BeneficiaryRestored, 'Restored access for'],
                WorkspaceBeneficiaryAccessAction::Revoke => [WorkspaceActivityType::BeneficiaryRevoked, 'Revoked access for'],
            };
            $this->activities->record($workspace, new WorkspaceActivityData(
                type: $type,
                title: "{$verb} {$name}",
                actor: WorkspaceActivityActor::user($user),
                subjectType: 'workspace_beneficiary',
                subjectId: $updated->public_id,
                subjectName: $name,
            ));

            return $updated;
        });
    }

    private function suspend(WorkspaceBeneficiary $beneficiary): WorkspaceBeneficiary
    {
        if (! $beneficiary->isActive()) {
            throw ValidationException::withMessages([
                'access' => 'Only an active beneficiary or employee can be suspended.',
            ]);
        }

        $beneficiary->update([
            'status' => WorkspaceBeneficiaryStatus::Suspended,
            'suspended_at' => now(),
        ]);

        return $beneficiary;
    }

    private function restore(WorkspaceBeneficiary $beneficiary): WorkspaceBeneficiary
    {
        if (! $beneficiary->isSuspended()) {
            throw ValidationException::withMessages([
                'access' => 'Only a suspended beneficiary or employee can have access restored.',
            ]);
        }

        $beneficiary->update([
            'status' => WorkspaceBeneficiaryStatus::Active,
            'suspended_at' => null,
        ]);

        return $beneficiary;
    }

    private function revoke(WorkspaceBeneficiary $beneficiary): WorkspaceBeneficiary
    {
        if (! $beneficiary->isActive() && ! $beneficiary->isSuspended()) {
            throw ValidationException::withMessages([
                'access' => 'Only an active or suspended beneficiary or employee can be revoked.',
            ]);
        }

        $beneficiary->update([
            'status' => WorkspaceBeneficiaryStatus::Revoked,
            'revoked_at' => now(),
        ]);

        return $beneficiary;
    }
}
