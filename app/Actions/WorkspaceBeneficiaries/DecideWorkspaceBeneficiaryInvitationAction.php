<?php

namespace App\Actions\WorkspaceBeneficiaries;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\WorkspaceBeneficiary;
use App\Services\Activity\WorkspaceActivityLogger;
use Illuminate\Support\Facades\DB;

final readonly class DecideWorkspaceBeneficiaryInvitationAction
{
    public function __construct(private WorkspaceActivityLogger $activities) {}

    public function execute(WorkspaceBeneficiary $invitation, string $decision): WorkspaceBeneficiary
    {
        return DB::transaction(function () use ($invitation, $decision): WorkspaceBeneficiary {
            $locked = WorkspaceBeneficiary::query()
                ->whereKey($invitation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->hasExpired()) {
                $locked->update(['status' => WorkspaceBeneficiaryStatus::Expired]);

                return $locked;
            }

            if (! $locked->isPending()) {
                return $locked;
            }

            $now = now();
            $locked->update($decision === 'accept'
                ? ['status' => WorkspaceBeneficiaryStatus::Active, 'accepted_at' => $now]
                : ['status' => WorkspaceBeneficiaryStatus::Declined, 'declined_at' => $now]);

            $workspace = $locked->workspace()->firstOrFail();
            $accepted = $decision === 'accept';
            $name = trim("{$locked->first_name} {$locked->last_name}");
            $this->activities->record($workspace, new WorkspaceActivityData(
                type: $accepted
                    ? WorkspaceActivityType::InvitationAccepted
                    : WorkspaceActivityType::InvitationDeclined,
                title: $accepted ? "{$name} accepted the invitation" : "{$name} declined the invitation",
                actor: WorkspaceActivityActor::beneficiary($locked),
                subjectType: 'workspace_beneficiary',
                subjectId: $locked->public_id,
                subjectName: $name,
            ));

            return $locked;
        });
    }
}
