<?php

namespace App\Actions\WorkspaceBeneficiaries;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\DTOs\WorkspaceBeneficiaries\EmployeeImportResult;
use App\DTOs\WorkspaceBeneficiaries\ImportRowError;
use App\DTOs\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\CampaignStatus;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Campaign;
use App\Models\CampaignBeneficiaryImport;
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
use Illuminate\Validation\ValidationException;

final readonly class ImportCampaignBeneficiariesAction
{
    public function __construct(
        private EmployeeImportReader $reader,
        private BeneficiaryLookupService $beneficiaries,
        private CampaignBeneficiaryCapacityService $capacity,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(
        Workspace $workspace,
        Campaign $campaign,
        User $inviter,
        UploadedFile|string $source,
    ): EmployeeImportResult {
        $parsed = $source instanceof UploadedFile
            ? $this->reader->read($source, requiresEmployeeFields: false, requiresCommunity: true)
            : $this->reader->readPasted($source, requiresEmployeeFields: false, requiresCommunity: true);
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
            Workspace::query()->whereKey($workspace->getKey())->lockForUpdate()->firstOrFail();
            $campaign = $this->capacity->lockCampaign($workspace, $campaign);

            if ($campaign->lifecycleStatus() === CampaignStatus::COMPLETED) {
                throw ValidationException::withMessages([
                    'campaign' => 'Beneficiaries cannot be imported into an ended campaign.',
                ]);
            }

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
            $enrolledElsewhere = WorkspaceBeneficiary::query()
                ->whereBelongsTo($workspace)
                ->where('relatable_type', $campaign->getMorphClass())
                ->where('relatable_id', '!=', $campaign->getKey())
                ->whereIn('email', $emails)
                ->consumingCapacity()
                ->lockForUpdate()
                ->pluck('email')
                ->flip();
            $invitations = [];
            $errors = [];
            $communities = collect(explode(',', (string) $campaign->location))
                ->map(static fn (string $community): string => mb_strtolower(trim($community)))
                ->filter();

            foreach ($parsed->rows as $row) {
                $data = $row['data'];
                $existing = $existingByEmail->get($data->email);

                if ($enrolledElsewhere->has($data->email)) {
                    $errors[] = new ImportRowError(
                        row: $row['row'],
                        errors: ['This beneficiary is already enrolled in another campaign in this workspace.'],
                        identifier: $data->email,
                        code: 'ALREADY_ENROLLED',
                    );

                    continue;
                }

                if ($data->community === null || ! $communities->contains(mb_strtolower($data->community))) {
                    $errors[] = new ImportRowError(
                        row: $row['row'],
                        errors: ['The community must be one of this campaign’s locations.'],
                        identifier: $data->email,
                        code: 'BAD_COMMUNITY',
                    );

                    continue;
                }

                if ($existing instanceof WorkspaceBeneficiary
                    && in_array($existing->status, [
                        WorkspaceBeneficiaryStatus::Active,
                        WorkspaceBeneficiaryStatus::Suspended,
                        WorkspaceBeneficiaryStatus::Pending,
                    ], true)) {
                    $errors[] = new ImportRowError(
                        row: $row['row'],
                        errors: ['This email already has an active, suspended, or pending record for this campaign.'],
                        identifier: $data->email,
                        code: 'DUPLICATE_EMAIL',
                    );

                    continue;
                }

                if ($campaign->beneficiary_limit !== null && $used >= $campaign->beneficiary_limit) {
                    $errors[] = new ImportRowError(
                        row: $row['row'],
                        errors: ['This campaign has reached its beneficiary limit.'],
                        identifier: $data->email,
                        code: 'CAPACITY_REACHED',
                    );

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

        $errors = [...$parsed->errors, ...$writeErrors];
        $publicId = (string) Str::ulid();

        CampaignBeneficiaryImport::query()->create([
            'public_id' => $publicId,
            'campaign_id' => $campaign->getKey(),
            'workspace_id' => $workspace->getKey(),
            'created_by_user_id' => $inviter->getKey(),
            'processed_count' => count($invitations) + count($errors),
            'enrolled_count' => count($invitations),
            'skipped_count' => count($errors),
            'errors' => array_map(static fn (ImportRowError $error): array => [
                'row' => $error->row,
                'identifier' => $error->identifier,
                'code' => $error->code,
                'message' => $error->errors[0] ?? 'The row could not be imported.',
            ], $errors),
        ]);

        return new EmployeeImportResult(count($invitations), count($errors), $errors, $publicId);
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
            'community' => $data->community ?? $campaign->location,
            'department' => null,
            'employee_id' => null,
            'status' => WorkspaceBeneficiaryStatus::Active,
            'source' => $data->source,
            'invitation_version' => $existing === null ? 1 : $existing->invitation_version + 1,
            'invited_at' => $now,
            'expires_at' => $now,
            'accepted_at' => $now,
            'declined_at' => null,
            'cancelled_at' => null,
            'suspended_at' => null,
            'revoked_at' => null,
        ])->save();

        return $invitation;
    }
}
