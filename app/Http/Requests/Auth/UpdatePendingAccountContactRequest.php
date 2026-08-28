<?php

namespace App\Http\Requests\Auth;

use App\Enums\AccountTypes;
use App\Models\User;
use App\ValueObjects\NigeriaPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePendingAccountContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->type === AccountTypes::INSTITUTION
            && ! $user->hasVerifiedAccount();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user),
            ],
            'phone' => [
                'required',
                'regex:/^\+234\d{10}$/',
                Rule::unique(User::class)->ignore($user),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->string('email')->lower()->trim()->toString(),
            'phone' => NigeriaPhoneNumber::normalize($this->string('phone')->toString()),
        ]);
    }
}
