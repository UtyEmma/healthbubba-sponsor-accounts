<?php

namespace App\Actions\Fortify;

use App\Actions\Organizations\CreateNewOrganization;
use App\Enums\AccountTypes;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

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
                Rule::requiredIf(fn() => !in_array($input['type'], [AccountTypes::INDIVIDUAL->value]))
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'type' => ['required', 'string', Rule::enum(AccountTypes::class)],
            'password' => $this->passwordRules(),
        ])->validate();

        DB::beginTransaction();

        $user_data = collect($input)
                        ->only(['name', 'email', 'type'])
                        ->merge([
                            'password' => Hash::make($input['password'])
                        ])->toArray();

        $user = User::create($user_data);

        if(!in_array($input['type'], [AccountTypes::INDIVIDUAL->value])) {
            $org_data = [
                'name' => $input['organization_name'],
                'type' => $input['type']
            ];

            $organization = (new CreateNewOrganization)->execute($user, $org_data);

            if(!$organization) throw ValidationException::withMessages([
                'organization_name' => "The user already belongs to an organization"
            ]);
        }

        DB::commit();

        return $user;
    }
}
