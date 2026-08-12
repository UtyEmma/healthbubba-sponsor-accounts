<?php

namespace App\Actions\WorkspaceBeneficiaries;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\DTOs\WorkspaceBeneficiaries\EmployeeImportResult;
use App\DTOs\WorkspaceBeneficiaries\ImportRowError;
use App\DTOs\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Services\WorkspaceBeneficiaries\BeneficiaryLookupService;
use App\Services\WorkspaceBeneficiaries\EmployeeIdGenerator;
use App\Services\WorkspaceBeneficiaries\EmployeeImportReader;
use App\Services\WorkspaceBeneficiaries\WorkspaceBeneficiaryCapacityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class ImportWorkspaceEmployeesAction
{
    public function __construct(
        private EmployeeImportReader $reader,
        private BeneficiaryLookupService $beneficiaries,
        private WorkspaceBeneficiaryCapacityService $capacity,
        private EmployeeIdGenerator $employeeIds,
        private SendWorkspaceBeneficiaryInvitationAction $sendInvitation,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(Workspace $workspace, User $inviter, UploadedFile $file): EmployeeImportResult
    {
        $parsed = $this->reader->read($file);
        $beneficiaryIds = $this->beneficiaries->idsByEmail(array_map(
            static fn (array $row): string => $row['data']->email,
            $parsed->rows,
        ));

        [$invitations, $writeErrors] = DB::transaction(function () use ($workspace, $inviter, $parsed, $beneficiaryIds): array {
            $subscription = $this->capacity->lockSubscription($workspace);
            $this->capacity->expirePending($workspace);
            $used = $this->capacity->used($workspace);
            $emails = array_map(static fn (array $row): string => $row['data']->email, $parsed->rows);
            $existingByEmail = $workspace->workspaceBeneficiaries()
                ->whereIn('email', $emails)
                ->lockForUpdate()
                ->get()
                ->keyBy('email');
            $usedEmployeeIds = $workspace->workspaceBeneficiaries()
                ->whereNotNull('employee_id')
                ->pluck('id', 'employee_id');
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
                    $errors[] = new ImportRowError($row['row'], ['This email already has an active, suspended, or pending workspace record.']);

                    continue;
                }

                if ($used >= $subscription->capacity_count) {
                    $errors[] = new ImportRowError($row['row'], ['This row exceeds the remaining employee-seat capacity.']);

                    continue;
                }

                $employeeId = $data->employeeId ?? $this->employeeIds->generate($workspace);
                $employeeOwner = $usedEmployeeIds->get($employeeId);

                if ($employeeOwner !== null && (! $existing instanceof WorkspaceBeneficiary || $employeeOwner !== $existing->getKey())) {
                    $errors[] = new ImportRowError($row['row'], ['This employee ID is already in use in the workspace.']);

                    continue;
                }

                $invitation = $this->persist(
                    workspace: $workspace,
                    inviter: $inviter,
                    data: $data,
                    employeeId: $employeeId,
                    beneficiaryId: $beneficiaryIds->get($data->email),
                    existing: $existing instanceof WorkspaceBeneficiary ? $existing : null,
                );
                $existingByEmail->put($data->email, $invitation);
                $usedEmployeeIds->put($employeeId, $invitation->getKey());
                $invitations[] = $invitation;
                $used++;
            }

            if ($invitations !== []) {
                $count = count($invitations);
                $this->activities->record($workspace, new WorkspaceActivityData(
                    type: WorkspaceActivityType::EmployeeImportCompleted,
                    title: "Imported {$count} employee invitation".($count === 1 ? '' : 's'),
                    actor: WorkspaceActivityActor::user($inviter),
                    subjectType: 'employee_import',
                    subjectId: null,
                    subjectName: 'Employee import',
                    context: ['quantity' => $count],
                ));
            }

            return [$invitations, $errors];
        });

        foreach ($invitations as $invitation) {
            $this->sendInvitation->execute($invitation);
        }

        $errors = [...$parsed->errors, ...$writeErrors];

        return new EmployeeImportResult(count($invitations), count($errors), $errors);
    }

    private function persist(
        Workspace $workspace,
        User $inviter,
        InviteWorkspaceBeneficiaryData $data,
        string $employeeId,
        mixed $beneficiaryId,
        ?WorkspaceBeneficiary $existing,
    ): WorkspaceBeneficiary {
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
            'department' => $data->department,
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

        return $invitation;
    }
}
