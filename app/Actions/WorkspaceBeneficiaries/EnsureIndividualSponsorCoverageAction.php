<?php

namespace App\Actions\WorkspaceBeneficiaries;

use App\Enums\AccountTypes;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiarySource;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\Beneficiary;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Models\WorkspaceMember;
use App\Services\WorkspaceBeneficiaries\BeneficiaryLookupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class EnsureIndividualSponsorCoverageAction
{
    public function __construct(private BeneficiaryLookupService $beneficiaries) {}

    public function execute(Workspace $workspace, ?Beneficiary $patient = null): ?WorkspaceBeneficiary
    {
        if ($workspace->type !== AccountTypes::INDIVIDUAL) {
            return null;
        }

        return DB::transaction(function () use ($workspace, $patient): ?WorkspaceBeneficiary {
            $lockedWorkspace = Workspace::query()
                ->whereKey($workspace->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedWorkspace->type !== AccountTypes::INDIVIDUAL) {
                return null;
            }

            $ownerMembership = WorkspaceMember::query()
                ->whereBelongsTo($lockedWorkspace)
                ->where('role', WorkspaceMemberRole::Owner)
                ->where('status', WorkspaceMemberStatus::Active)
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $ownerMembership instanceof WorkspaceMember || $ownerMembership->user_id === null) {
                return null;
            }

            $owner = User::query()->whereKey($ownerMembership->user_id)->lockForUpdate()->first();

            if (! $owner instanceof User) {
                return null;
            }

            $email = mb_strtolower(trim($owner->email));

            if ($patient instanceof Beneficiary
                && mb_strtolower(trim((string) $patient->email)) !== $email) {
                return null;
            }

            $patientId = $patient?->getKey()
                ?? $this->beneficiaries->idsByEmail([$email])->get($email);
            $primaryCoverage = WorkspaceBeneficiary::query()
                ->whereBelongsTo($lockedWorkspace)
                ->whereNotNull('primary_sponsor_user_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
            $matchingCoverage = WorkspaceBeneficiary::query()
                ->whereBelongsTo($lockedWorkspace)
                ->where('relatable_type', $lockedWorkspace->getMorphClass())
                ->where('relatable_id', $lockedWorkspace->getKey())
                ->whereRaw('LOWER(email) = ?', [$email])
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($primaryCoverage instanceof WorkspaceBeneficiary
                && $primaryCoverage->primary_sponsor_user_id !== $owner->getKey()
                && ! $primaryCoverage->is($matchingCoverage)) {
                $primaryCoverage->update([
                    'primary_sponsor_user_id' => null,
                    'status' => WorkspaceBeneficiaryStatus::Revoked,
                    'revoked_at' => now(),
                ]);
            }

            $coverage = $matchingCoverage
                ?? ($primaryCoverage?->primary_sponsor_user_id === $owner->getKey() ? $primaryCoverage : null);
            [$firstName, $lastName] = $this->splitName($owner->name);
            $now = now();

            $coverage ??= new WorkspaceBeneficiary([
                'workspace_id' => $lockedWorkspace->getKey(),
                'public_id' => (string) Str::ulid(),
                'source' => WorkspaceBeneficiarySource::Manual,
                'invitation_version' => 1,
                'invited_at' => $now,
                'expires_at' => $now,
            ]);
            $coverage->relatable()->associate($lockedWorkspace);
            $coverage->fill([
                'invited_by_user_id' => $owner->getKey(),
                'primary_sponsor_user_id' => $owner->getKey(),
                'beneficiary_id' => $patientId ?? $coverage->beneficiary_id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => (string) ($owner->phone ?? ''),
                'status' => WorkspaceBeneficiaryStatus::Active,
                'accepted_at' => $coverage->accepted_at ?? $now,
                'declined_at' => null,
                'cancelled_at' => null,
                'suspended_at' => null,
                'revoked_at' => null,
            ])->save();

            return $coverage->refresh();
        });
    }

    /** @return array{string, string} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [$parts[0] ?? 'Sponsor', $parts[1] ?? ''];
    }
}
