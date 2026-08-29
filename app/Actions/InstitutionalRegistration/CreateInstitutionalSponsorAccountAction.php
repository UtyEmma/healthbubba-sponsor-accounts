<?php

namespace App\Actions\InstitutionalRegistration;

use App\DTOs\InstitutionalRegistration\InstitutionalSponsorAccount;
use App\DTOs\InstitutionalRegistration\InstitutionalSponsorRegistrationData;
use App\Enums\AccountTypes;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final readonly class CreateInstitutionalSponsorAccountAction
{
    public function execute(InstitutionalSponsorRegistrationData $data): InstitutionalSponsorAccount
    {
        return DB::transaction(function () use ($data): InstitutionalSponsorAccount {
            $user = User::query()->create([
                'name' => $data->ownerName,
                'email' => $data->ownerEmail,
                'phone' => $data->ownerPhone,
                'type' => AccountTypes::INSTITUTION,
                'password' => Hash::make($data->password),
                'account_verified_at' => null,
            ]);

            $workspace = Workspace::query()->create([
                'name' => $data->organizationName,
                'type' => AccountTypes::INSTITUTION,
                'organization_type' => $data->organizationType,
                'country_code' => $data->countryCode,
                'state_code' => $data->state,
                'official_email' => $data->officialEmail,
                'official_phone' => $data->officialPhone,
            ]);

            $startsOn = now()->startOfDay();
            $workspace->fundingProgram()->create([
                'name' => 'Community Health Program '.$startsOn->year,
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => $startsOn->copy()->addYearNoOverflow()->toDateString(),
            ]);
            $workspace->members()->create([
                'public_id' => (string) Str::ulid(),
                'user_id' => $user->getKey(),
                'name' => $data->ownerName,
                'email' => $data->ownerEmail,
                'phone' => $data->ownerPhone,
                'job_title' => $data->jobTitle,
                'role' => WorkspaceMemberRole::Owner,
                'status' => WorkspaceMemberStatus::Active,
                'authorization_confirmed_at' => now(),
                'accepted_at' => now(),
                'last_selected_at' => now(),
            ]);

            return new InstitutionalSponsorAccount($user, $workspace);
        });
    }
}
