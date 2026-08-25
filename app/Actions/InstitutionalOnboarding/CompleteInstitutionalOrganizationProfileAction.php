<?php

namespace App\Actions\InstitutionalOnboarding;

use App\DTOs\InstitutionalOnboarding\InstitutionalCampaignOnboardingData;
use App\Enums\AccountTypes;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Events\InstitutionalOnboarding\InstitutionalAccountCreated;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CompleteInstitutionalOrganizationProfileAction
{
    public function execute(
        Workspace $workspace,
        User $owner,
        InstitutionalCampaignOnboardingData $data,
    ): Workspace {
        return DB::transaction(function () use ($workspace, $owner, $data): Workspace {
            $lockedWorkspace = Workspace::query()
                ->whereKey($workspace->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedWorkspace->type !== AccountTypes::INSTITUTION) {
                throw ValidationException::withMessages([
                    'organization' => 'Only institutional organizations use this onboarding flow.',
                ]);
            }

            $membership = WorkspaceMember::query()
                ->whereBelongsTo($lockedWorkspace)
                ->whereBelongsTo($owner)
                ->where('role', WorkspaceMemberRole::Owner)
                ->where('status', WorkspaceMemberStatus::Active)
                ->lockForUpdate()
                ->first();

            if (! $membership instanceof WorkspaceMember) {
                throw ValidationException::withMessages([
                    'organization' => 'Only the active workspace owner may complete institutional onboarding.',
                ]);
            }

            if ($lockedWorkspace->campaigns()->exists()) {
                return $lockedWorkspace;
            }

            $lockedWorkspace->campaigns()->create([
                'name' => $data->campaignName,
                'location' => $data->campaignLocation,
                'city' => $data->city,
                'state' => $data->state,
                'target_audience' => $data->targetAudience,
                'beneficiary_limit' => $data->beneficiaryLimit,
                'start_date' => $data->startDate,
                'end_date' => $data->endDate,
                'booth_required' => $data->boothRequired,
            ]);
            $lockedWorkspace->update(['onboarded_at' => now()]);

            InstitutionalAccountCreated::dispatch(
                workspaceId: (int) $lockedWorkspace->getKey(),
                ownerId: (int) $owner->getKey(),
            );

            return $lockedWorkspace;
        });
    }
}
