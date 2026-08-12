<?php

namespace App\Http\Requests\WorkspaceBeneficiaries;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DecideWorkspaceBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(['accept', 'decline'])]];
    }
}
