<?php

namespace App\Actions\Admin;

use App\Actions\Workspaces\CreateNewWorkspace;
use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\DTOs\Admin\AssignWorkspaceSubscriptionData;
use App\DTOs\Admin\ProvisionSponsorAccountData;
use App\Enums\Account\Roles;
use App\Enums\Activity\WorkspaceActivityType;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\AdminAccountAccessInvitation;
use App\Services\Activity\WorkspaceActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class ProvisionSponsorAccountAction
{
    public function __construct(
        private CreateNewWorkspace $createWorkspace,
        private AssignWorkspaceSubscriptionAction $assignSubscription,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(User $administrator, ProvisionSponsorAccountData $data): Workspace
    {
        abort_unless($administrator->isAdmin(), 403);

        return DB::transaction(function () use ($administrator, $data): Workspace {
            [$user, $isNew] = $this->resolveIdentity($administrator, $data);
            $workspace = $this->createWorkspace->execute($user, $data->workspace);

            if ($data->subscription instanceof AssignWorkspaceSubscriptionData) {
                $this->assignSubscription->execute($administrator, new AssignWorkspaceSubscriptionData(
                    workspaceId: (int) $workspace->getKey(),
                    planId: $data->subscription->planId,
                    fundingSource: $data->subscription->fundingSource,
                    additionalCapacity: $data->subscription->additionalCapacity,
                    complimentaryRenews: $data->subscription->complimentaryRenews,
                ));
            }

            $this->activities->record($workspace, new WorkspaceActivityData(
                type: WorkspaceActivityType::SponsorAccountProvisioned,
                title: 'Sponsor account provisioned',
                actor: WorkspaceActivityActor::user($administrator),
                subjectType: 'workspace',
                subjectId: $workspace->getKey(),
                subjectName: $workspace->name,
                description: $isNew
                    ? 'An administrator created the sponsor identity and workspace.'
                    : 'An administrator added a new sponsor workspace to an existing identity.',
                context: [
                    'account_type' => $workspace->type->value,
                    'new_identity' => $isNew,
                ],
            ));

            if ($isNew && ($data->sendInvitation || $data->password === null)) {
                $token = Password::broker()->createToken($user);
                $user->notify(new AdminAccountAccessInvitation($token, $workspace->name));
            }

            return $workspace->load(['members.user', 'ownerMembership.user', 'subscriptions.plan', 'wallet']);
        }, 3);
    }

    /** @return array{User, bool} */
    private function resolveIdentity(User $administrator, ProvisionSponsorAccountData $data): array
    {
        $email = Str::lower(trim($data->email));
        $query = User::query()->lockForUpdate();
        $user = $data->existingUserId === null
            ? $query->whereRaw('LOWER(email) = ?', [$email])->first()
            : $query->whereKey($data->existingUserId)->firstOrFail();

        if ($user instanceof User) {
            if (Str::lower(trim($user->email)) !== $email) {
                throw ValidationException::withMessages([
                    'email' => 'The selected identity does not match the supplied email address.',
                ]);
            }

            if ($user->role !== Roles::USER && ! $administrator->isSuperAdmin()) {
                throw ValidationException::withMessages([
                    'existing_user_id' => 'Only a super administrator may provision workspaces for administrative identities.',
                ]);
            }

            return [$user, false];
        }

        if ($data->existingUserId !== null) {
            throw ValidationException::withMessages(['existing_user_id' => 'The selected user no longer exists.']);
        }

        if ($data->role !== Roles::USER && ! $administrator->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'role' => 'Only a super administrator may assign an administrative role.',
            ]);
        }

        $password = $data->password ?? Str::password(48);
        $now = now();
        $user = User::query()->create([
            'name' => Str::squish($data->name),
            'email' => $email,
            'phone' => $data->phone,
            'type' => $data->workspace->accountType,
            'role' => $data->role,
            'status' => $data->status,
            'password' => Hash::make($password),
        ]);
        $user->forceFill([
            'email_verified_at' => $data->emailVerified ? $now : null,
            'phone_verified_at' => $data->phoneVerified ? $now : null,
            'account_verified_at' => $data->accountVerified ? $now : null,
        ])->save();

        return [$user, true];
    }
}
