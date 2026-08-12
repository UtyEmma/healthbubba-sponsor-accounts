<?php

namespace App\Actions\MedicalAccess;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\MedicalAccess\MedicalAccessRequestStatus;
use App\Models\MedicalAccessRequest;
use App\Models\WorkspaceBeneficiary;
use App\Services\Activity\WorkspaceActivityLogger;
use Illuminate\Support\Facades\DB;

final readonly class DecideMedicalAccessRequestAction
{
    public function __construct(
        private ExpireMedicalAccessRequestsAction $expireRequests,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(MedicalAccessRequest $medicalAccessRequest, string $decision): MedicalAccessRequest
    {
        return DB::transaction(function () use ($medicalAccessRequest, $decision): MedicalAccessRequest {
            $locked = MedicalAccessRequest::query()
                ->whereKey($medicalAccessRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $beneficiary = WorkspaceBeneficiary::query()
                ->whereKey($locked->workspace_beneficiary_id)
                ->lockForUpdate()
                ->firstOrFail();
            $locked->setRelation('workspaceBeneficiary', $beneficiary);

            if ($this->expireRequests->expireLocked($locked) || ! $locked->isPending()) {
                return $locked;
            }

            $now = now();
            $locked->update($decision === 'allow'
                ? [
                    'status' => MedicalAccessRequestStatus::Approved,
                    'approved_at' => $now,
                    'access_expires_at' => $now->copy()->addDays(30),
                ]
                : [
                    'status' => MedicalAccessRequestStatus::Denied,
                    'denied_at' => $now,
                ]);

            $workspace = $locked->workspace()->firstOrFail();
            $approved = $decision === 'allow';
            $name = trim("{$beneficiary->first_name} {$beneficiary->last_name}");
            $this->activities->record($workspace, new WorkspaceActivityData(
                type: $approved
                    ? WorkspaceActivityType::MedicalAccessApproved
                    : WorkspaceActivityType::MedicalAccessDenied,
                title: $approved
                    ? "{$name} allowed {$locked->data_type->label()} access"
                    : "{$name} denied {$locked->data_type->label()} access",
                actor: WorkspaceActivityActor::beneficiary($beneficiary),
                subjectType: 'medical_access_request',
                subjectId: $locked->public_id,
                subjectName: $name,
                context: ['data_type' => $locked->data_type->value],
            ));

            return $locked;
        });
    }
}
