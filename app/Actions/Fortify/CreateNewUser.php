<?php

namespace App\Actions\Fortify;

use App\Actions\Marketing\AttributeSignupReferrerAction;
use App\Actions\Workspaces\CreateNewWorkspace;
use App\DTOs\Workspaces\CreateWorkspaceData;
use App\Enums\AccountTypes;
use App\Models\User;
use App\Rules\MarketingReferralCode;
use App\ValueObjects\NigeriaPhoneNumber;
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

    public function __construct(private readonly CreateNewWorkspace $createWorkspace, private readonly AttributeSignupReferrerAction $attributeReferrer) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $phone = is_string($input['phone'] ?? null)
            ? NigeriaPhoneNumber::normalize($input['phone'])
            : null;
        $input['phone'] = $phone === '' ? null : $phone;
        $input['referral_code'] = is_string($input['referral_code'] ?? null)
            ? Str::lower(trim($input['referral_code']))
            : ($input['referral_code'] ?? null);
        Validator::make($input, [
            'referral_code' => ['nullable', 'string', new MarketingReferralCode],
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
            'phone' => ['nullable', 'regex:/^\+234\d{10}$/', Rule::unique(User::class)],
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
                'phone' => $input['phone'],
                'type' => $accountType,
                'password' => Hash::make($input['password']),
                'account_verified_at' => now(),
            ]);

            $workspaceName = $accountType === AccountTypes::INDIVIDUAL
                ? $user->name
                : Str::squish($input['organization_name']);

            $this->createWorkspace->execute($user, new CreateWorkspaceData(
                name: $workspaceName,
                accountType: $accountType,
                memberPhone: $user->phone,
            ));

            $this->attributeReferrer->execute($user, $input['referral_code'] ?? null);

            return $user;
        });
    }
}
