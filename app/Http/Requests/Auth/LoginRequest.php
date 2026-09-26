<?php

namespace App\Http\Requests\Auth;

use App\Enums\AccountTypes;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

final class LoginRequest extends FortifyLoginRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'account_type' => ['required', Rule::enum(AccountTypes::class)],
            ...parent::rules(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'account_type.required' => 'Select the account type you want to sign in to.',
            'account_type.enum' => 'Select a valid account type to sign in to.',
        ];
    }
}
