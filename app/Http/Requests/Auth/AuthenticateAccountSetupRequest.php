<?php

namespace App\Http\Requests\Auth;

use App\Enums\AccountTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AuthenticateAccountSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'account_type' => ['required', Rule::enum(AccountTypes::class)],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->string('email')->lower()->trim()->toString(),
        ]);
    }

    public function accountType(): AccountTypes
    {
        return AccountTypes::from((string) $this->validated('account_type'));
    }

    public function email(): string
    {
        return (string) $this->validated('email');
    }

    public function password(): string
    {
        return (string) $this->validated('password');
    }
}
