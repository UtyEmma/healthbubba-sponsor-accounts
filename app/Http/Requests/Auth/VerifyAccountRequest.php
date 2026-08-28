<?php

namespace App\Http\Requests\Auth;

use App\Enums\AccountTypes;
use App\Enums\VerificationChannel;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VerifyAccountRequest extends FormRequest
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
        return [
            'channel' => ['required', Rule::enum(VerificationChannel::class)],
            'code' => ['required', 'digits:6'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => preg_replace('/\D+/', '', $this->string('code')->toString()) ?? '',
        ]);
    }

    public function channel(): VerificationChannel
    {
        return VerificationChannel::from((string) $this->validated('channel'));
    }
}
