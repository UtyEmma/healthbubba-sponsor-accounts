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

final readonly class ResendWorkspaceBeneficiaryInvitationAction
{
    public function __construct(
        private WorkspaceBeneficiaryCapacityService $capacity,
        private SendWorkspaceBeneficiaryInvitationAction $sendInvitation,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(Workspace $workspace, User $inviter, WorkspaceBeneficiary $invitation): WorkspaceBeneficiary
    {
        $invitation = DB::transaction(function () use ($workspace, $inviter, $invitation): WorkspaceBeneficiary {
            $subscription = $this->capacity->lockSubscription($workspace);
            $this->capacity->expirePending($workspace);
            $locked = $workspace->workspaceBeneficiaries()
                ->whereKey($invitation->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $wasConsuming = $locked->status === WorkspaceBeneficiaryStatus::Pending
                && $locked->expires_at->isFuture();

            if (! in_array($locked->status, [
                WorkspaceBeneficiaryStatus::Pending,
                WorkspaceBeneficiaryStatus::Declined,
                WorkspaceBeneficiaryStatus::Cancelled,
                WorkspaceBeneficiaryStatus::Expired,
            ], true)) {
                throw ValidationException::withMessages(['invitation' => 'This workspace record cannot be resent an invitation.']);
            }

            if (! $wasConsuming && $this->capacity->used($workspace) >= $subscription->capacity_count) {
                throw ValidationException::withMessages(['capacity' => 'There are no remaining seats on the current plan.']);
            }

            $now = now();
            $locked->update([
                'invited_by_user_id' => $inviter->getKey(),
                'status' => WorkspaceBeneficiaryStatus::Pending,
                'invitation_version' => $locked->invitation_version + 1,
                'invited_at' => $now,
                'expires_at' => $now->copy()->addDay(),
                'accepted_at' => null,
                'declined_at' => null,
                'cancelled_at' => null,
                'suspended_at' => null,
                'revoked_at' => null,
            ]);

            $name = trim("{$locked->first_name} {$locked->last_name}");
            $this->activities->record($workspace, new WorkspaceActivityData(
                type: WorkspaceActivityType::InvitationResent,
                title: "Resent invitation to {$name}",
                actor: WorkspaceActivityActor::user($inviter),
                subjectType: 'workspace_beneficiary',
                subjectId: $locked->public_id,
                subjectName: $name,
            ));

            return $locked;
        });

        $this->sendInvitation->execute($invitation);

        return $invitation;
    }
}
