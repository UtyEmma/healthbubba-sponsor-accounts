<?php

namespace App\Actions\InstitutionalRegistration;

use App\Actions\Workspaces\CreateNewWorkspace;
use App\DTOs\InstitutionalRegistration\InstitutionalSponsorAccount;
use App\DTOs\InstitutionalRegistration\InstitutionalSponsorRegistrationData;
use App\DTOs\Workspaces\CreateWorkspaceData;
use App\Enums\AccountTypes;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class CreateInstitutionalSponsorAccountAction
{
    public function __construct(private CreateNewWorkspace $createWorkspace) {}

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

            $workspace = $this->createWorkspace->execute($user, new CreateWorkspaceData(
                name: $data->organizationName,
                accountType: AccountTypes::INSTITUTION,
                organizationType: $data->organizationType,
                countryCode: $data->countryCode,
                state: $data->state,
                officialEmail: $data->officialEmail,
                officialPhone: $data->officialPhone,
                memberPhone: $data->ownerPhone,
                memberJobTitle: $data->jobTitle,
                authorizationConfirmed: true,
            ));

            return new InstitutionalSponsorAccount($user, $workspace);
        });
    }
}
