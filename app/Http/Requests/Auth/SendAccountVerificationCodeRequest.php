<?php

namespace App\Http\Requests\Auth;

use App\Enums\AccountTypes;
use App\Enums\VerificationChannel;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SendAccountVerificationCodeRequest extends FormRequest
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
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->string('channel')->toString() === VerificationChannel::Sms->value
                    && ! config('services.termii.configured')) {
                    $validator->errors()->add('channel', 'SMS verification is temporarily unavailable. Please use email.');
                }
            },
        ];
    }

    public function channel(): VerificationChannel
    {
        return VerificationChannel::from((string) $this->validated('channel'));
    }
}
