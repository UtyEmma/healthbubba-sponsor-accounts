<?php

namespace App\Actions\WorkspaceBeneficiaries;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Services\WorkspaceBeneficiaries\WorkspaceBeneficiaryCapacityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CancelWorkspaceBeneficiaryInvitationAction
{
    public function __construct(
        private WorkspaceBeneficiaryCapacityService $capacity,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(Workspace $workspace, User $user, WorkspaceBeneficiary $invitation): WorkspaceBeneficiary
    {
        return DB::transaction(function () use ($workspace, $user, $invitation): WorkspaceBeneficiary {
            $this->capacity->lockSubscription($workspace);
            $this->capacity->expirePending($workspace);
            $locked = $workspace->workspaceBeneficiaries()
                ->whereKey($invitation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== WorkspaceBeneficiaryStatus::Pending) {
                throw ValidationException::withMessages(['invitation' => 'Only a pending invitation can be cancelled.']);
            }

            $locked->update([
                'status' => WorkspaceBeneficiaryStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $name = trim("{$locked->first_name} {$locked->last_name}");
            $this->activities->record($workspace, new WorkspaceActivityData(
                type: WorkspaceActivityType::InvitationCancelled,
                title: "Cancelled invitation for {$name}",
                actor: WorkspaceActivityActor::user($user),
                subjectType: 'workspace_beneficiary',
                subjectId: $locked->public_id,
                subjectName: $name,
            ));

            return $locked;
        });
    }
}
