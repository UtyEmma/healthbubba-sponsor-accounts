<?php

namespace App\Actions\WorkspaceBeneficiaries;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\DTOs\WorkspaceBeneficiaries\EmployeeImportResult;
use App\DTOs\WorkspaceBeneficiaries\ImportRowError;
use App\DTOs\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Services\WorkspaceBeneficiaries\BeneficiaryLookupService;
use App\Services\WorkspaceBeneficiaries\CampaignBeneficiaryCapacityService;
use App\Services\WorkspaceBeneficiaries\EmployeeImportReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class ImportCampaignBeneficiariesAction
{
    public function __construct(
        private EmployeeImportReader $reader,
        private BeneficiaryLookupService $beneficiaries,
        private CampaignBeneficiaryCapacityService $capacity,
        private SendWorkspaceBeneficiaryInvitationAction $sendInvitation,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(
        Workspace $workspace,
        Campaign $campaign,
        User $inviter,
        UploadedFile $file,
    ): EmployeeImportResult {
        $parsed = $this->reader->read($file, requiresEmployeeFields: false);
        $beneficiaryIds = $this->beneficiaries->idsByEmail(array_map(
            static fn (array $row): string => $row['data']->email,
            $parsed->rows,
        ));

        [$invitations, $writeErrors] = DB::transaction(function () use (
            $workspace,
            $campaign,
            $inviter,
            $parsed,
            $beneficiaryIds,
        ): array {
            $campaign = $this->capacity->lockCampaign($workspace, $campaign);
            $this->capacity->expirePending($campaign);
            $used = $this->capacity->used($campaign);
            $emails = array_map(
                static fn (array $row): string => $row['data']->email,
                $parsed->rows,
            );
            $existingByEmail = $campaign->beneficiaries()
                ->whereIn('email', $emails)
                ->lockForUpdate()
                ->get()
                ->keyBy('email');
            $invitations = [];
            $errors = [];

            foreach ($parsed->rows as $row) {
                $data = $row['data'];
                $existing = $existingByEmail->get($data->email);

                if ($existing instanceof WorkspaceBeneficiary
                    && in_array($existing->status, [
                        WorkspaceBeneficiaryStatus::Active,
                        WorkspaceBeneficiaryStatus::Suspended,
                        WorkspaceBeneficiaryStatus::Pending,
                    ], true)) {
                    $errors[] = new ImportRowError($row['row'], [
                        'This email already has an active, suspended, or pending record for this campaign.',
                    ]);

                    continue;
                }

                if ($used >= $campaign->beneficiary_limit) {
                    $errors[] = new ImportRowError($row['row'], [
                        'This campaign has reached its beneficiary limit.',
                    ]);

                    continue;
                }

                $invitation = $this->persist(
                    workspace: $workspace,
                    campaign: $campaign,
                    inviter: $inviter,
                    data: $data,
                    beneficiaryId: $beneficiaryIds->get($data->email),
                    existing: $existing instanceof WorkspaceBeneficiary ? $existing : null,
                );
                $existingByEmail->put($data->email, $invitation);
                $invitations[] = $invitation;
                $used++;
            }

            if ($invitations !== []) {
                $count = count($invitations);
                $errorCount = count($parsed->errors) + count($errors);
                $this->activities->record($workspace, new WorkspaceActivityData(
                    type: WorkspaceActivityType::EmployeeImportCompleted,
                    title: "Bulk uploaded {$count} campaign beneficiar".($count === 1 ? 'y' : 'ies').
                        " ({$count} committed, {$errorCount} errors)",
                    actor: WorkspaceActivityActor::user($inviter),
                    subjectType: 'campaign_beneficiary_import',
                    subjectId: (string) $campaign->getKey(),
                    subjectName: $campaign->name,
                    context: ['quantity' => $count],
                ));
            }

            return [$invitations, $errors];
        }, 3);

        foreach ($invitations as $invitation) {
            $this->sendInvitation->execute($invitation);
        }

        $errors = [...$parsed->errors, ...$writeErrors];

        return new EmployeeImportResult(count($invitations), count($errors), $errors);
    }

    private function persist(
        Workspace $workspace,
        Campaign $campaign,
        User $inviter,
        InviteWorkspaceBeneficiaryData $data,
        mixed $beneficiaryId,
        ?WorkspaceBeneficiary $existing,
    ): WorkspaceBeneficiary {
        $now = now();
        $invitation = $existing ?? new WorkspaceBeneficiary([
            'workspace_id' => $workspace->getKey(),
            'public_id' => (string) Str::ulid(),
        ]);
        $invitation->relatable()->associate($campaign);
        $invitation->fill([
            'invited_by_user_id' => $inviter->getKey(),
            'beneficiary_id' => $beneficiaryId,
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $data->email,
            'phone' => $data->phone,
            'department' => null,
            'employee_id' => null,
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

        return $invitation;
    }
}
