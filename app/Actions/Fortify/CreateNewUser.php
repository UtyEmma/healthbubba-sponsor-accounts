<?php

namespace App\Actions\Fortify;

use App\Actions\Workspaces\CreateNewWorkspace;
use App\DTOs\Workspaces\CreateWorkspaceData;
use App\Enums\AccountTypes;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private readonly CreateNewWorkspace $createWorkspace) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'organization_name' => [
                Rule::requiredIf(fn (): bool => in_array($input['type'] ?? null, [
                    AccountTypes::BUSINESS->value,
                ], true)),
                'nullable',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'type' => [
                'required',
                'string',
                Rule::in([
                    AccountTypes::INDIVIDUAL->value,
                    AccountTypes::BUSINESS->value,
                ]),
            ],
            'password' => [...$this->passwordRules(), 'confirmed'],
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $accountType = AccountTypes::from($input['type']);
            $user = User::query()->create([
                'name' => Str::squish($input['name']),
                'email' => Str::lower(trim($input['email'])),
                'type' => $accountType,
                'password' => Hash::make($input['password']),
                'account_verified_at' => now(),
            ]);

            $workspaceName = $accountType === AccountTypes::INDIVIDUAL
                ? "{$user->name}'s Workspace"
                : Str::squish($input['organization_name']);

            $this->createWorkspace->execute($user, new CreateWorkspaceData(
                name: $workspaceName,
                accountType: $accountType,
                memberPhone: $user->phone,
            ));

            return $user;
        });
    }
}
