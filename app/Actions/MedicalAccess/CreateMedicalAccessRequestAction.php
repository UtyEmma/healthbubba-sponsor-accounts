<?php

namespace App\Actions\MedicalAccess;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\DTOs\MedicalAccess\CreateMedicalAccessRequestData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\MedicalAccess\MedicalAccessRequestStatus;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Mail\MedicalAccessRequestMail;
use App\Models\MedicalAccessRequest;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Services\MedicalAccess\MedicalAccessRequestUrlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class CreateMedicalAccessRequestAction
{
    public function __construct(
        private ExpireMedicalAccessRequestsAction $expireRequests,
        private MedicalAccessRequestUrlService $urls,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(
        Workspace $workspace,
        User $requester,
        CreateMedicalAccessRequestData $data,
    ): MedicalAccessRequest {
        $medicalAccessRequest = DB::transaction(function () use ($workspace, $requester, $data): MedicalAccessRequest {
            $beneficiary = WorkspaceBeneficiary::query()
                ->whereBelongsTo($workspace)
                ->where('public_id', $data->beneficiaryPublicId)
                ->lockForUpdate()
                ->first();

            if (! $beneficiary instanceof WorkspaceBeneficiary) {
                throw ValidationException::withMessages([
                    'beneficiary_public_id' => 'The selected beneficiary is not available in this workspace.',
                ]);
            }

            if ($beneficiary->status !== WorkspaceBeneficiaryStatus::Active || $beneficiary->beneficiary_id === null) {
                throw ValidationException::withMessages([
                    'beneficiary_public_id' => 'Medical access can only be requested for active beneficiaries with a HealthBubba account.',
                ]);
            }

            $this->expireRequests->executeForBeneficiary($beneficiary);

            $duplicateExists = $beneficiary->medicalAccessRequests()
                ->where('data_type', $data->dataType)
                ->current()
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'data_type' => 'A pending request or active approval already exists for this beneficiary and data type.',
                ]);
            }

            $now = now();

            $request = $beneficiary->medicalAccessRequests()->create([
                'public_id' => (string) Str::ulid(),
                'workspace_id' => $workspace->getKey(),
                'requested_by_user_id' => $requester->getKey(),
                'data_type' => $data->dataType,
                'reason' => $data->reason,
                'status' => MedicalAccessRequestStatus::Pending,
                'requested_at' => $now,
                'review_expires_at' => $now->copy()->addDay(),
            ]);

            $name = trim("{$beneficiary->first_name} {$beneficiary->last_name}");
            $this->activities->record($workspace, new WorkspaceActivityData(
                type: WorkspaceActivityType::MedicalAccessRequested,
                title: "Requested {$data->dataType->label()} access for {$name}",
                actor: WorkspaceActivityActor::user($requester),
                subjectType: 'medical_access_request',
                subjectId: $request->public_id,
                subjectName: $name,
                context: ['data_type' => $data->dataType->value],
            ));

            return $request;
        });

        $medicalAccessRequest->loadMissing(['workspace', 'workspaceBeneficiary']);
        Mail::to($medicalAccessRequest->workspaceBeneficiary->email)->send(
            new MedicalAccessRequestMail(
                beneficiaryName: trim("{$medicalAccessRequest->workspaceBeneficiary->first_name} {$medicalAccessRequest->workspaceBeneficiary->last_name}"),
                workspaceName: $medicalAccessRequest->workspace->name,
                dataType: $medicalAccessRequest->data_type->label(),
                reason: $medicalAccessRequest->reason,
                reviewUrl: $this->urls->review($medicalAccessRequest),
                expiresAt: $medicalAccessRequest->review_expires_at
                    ->timezone(config('app.timezone'))
                    ->toDayDateTimeString(),
            ),
        );

        return $medicalAccessRequest;
    }
}
