<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

final class PaymentCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9.=_-]+$/'],
        ];
    }

    public function reference(): string
    {
        return (string) $this->validated('reference');
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('reference') && $this->filled('trxref')) {
            $this->merge(['reference' => $this->input('trxref')]);
        }
    }
}
