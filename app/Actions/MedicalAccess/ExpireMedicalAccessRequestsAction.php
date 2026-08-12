<?php

namespace App\Actions\MedicalAccess;

use App\Enums\MedicalAccess\MedicalAccessRequestStatus;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\MedicalAccessRequest;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class ExpireMedicalAccessRequestsAction
{
    public function execute(?Workspace $workspace = null): int
    {
        $pending = MedicalAccessRequest::query()
            ->when($workspace, fn (Builder $query) => $query->whereBelongsTo($workspace))
            ->where('status', MedicalAccessRequestStatus::Pending)
            ->where('review_expires_at', '<=', now())
            ->update(['status' => MedicalAccessRequestStatus::Expired]);

        $approved = MedicalAccessRequest::query()
            ->when($workspace, fn (Builder $query) => $query->whereBelongsTo($workspace))
            ->where('status', MedicalAccessRequestStatus::Approved)
            ->where('access_expires_at', '<=', now())
            ->update(['status' => MedicalAccessRequestStatus::Expired]);

        return $pending + $approved;
    }

    public function executeForBeneficiary(WorkspaceBeneficiary $beneficiary): int
    {
        $pending = $beneficiary->medicalAccessRequests()
            ->where('status', MedicalAccessRequestStatus::Pending)
            ->where('review_expires_at', '<=', now())
            ->update(['status' => MedicalAccessRequestStatus::Expired]);

        $approved = $beneficiary->medicalAccessRequests()
            ->where('status', MedicalAccessRequestStatus::Approved)
            ->where('access_expires_at', '<=', now())
            ->update(['status' => MedicalAccessRequestStatus::Expired]);

        return $pending + $approved;
    }

    public function executeForRequest(MedicalAccessRequest $medicalAccessRequest): MedicalAccessRequest
    {
        return DB::transaction(function () use ($medicalAccessRequest): MedicalAccessRequest {
            $locked = MedicalAccessRequest::query()
                ->whereKey($medicalAccessRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $beneficiary = WorkspaceBeneficiary::query()
                ->whereKey($locked->workspace_beneficiary_id)
                ->lockForUpdate()
                ->firstOrFail();
            $locked->setRelation('workspaceBeneficiary', $beneficiary);

            $this->expireLocked($locked);

            return $locked;
        });
    }

    public function expireLocked(MedicalAccessRequest $medicalAccessRequest): bool
    {
        $beneficiary = $medicalAccessRequest->workspaceBeneficiary;
        $pendingExpired = $medicalAccessRequest->isPending()
            && ($medicalAccessRequest->review_expires_at->isPast()
                || $beneficiary->status !== WorkspaceBeneficiaryStatus::Active
                || $beneficiary->beneficiary_id === null);
        $grantExpired = $medicalAccessRequest->isApproved()
            && ($medicalAccessRequest->access_expires_at === null
                || $medicalAccessRequest->access_expires_at->isPast());

        if (! $pendingExpired && ! $grantExpired) {
            return false;
        }

        $medicalAccessRequest->update(['status' => MedicalAccessRequestStatus::Expired]);

        return true;
    }
}
