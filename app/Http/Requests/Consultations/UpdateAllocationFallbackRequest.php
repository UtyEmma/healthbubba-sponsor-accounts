<?php

namespace App\Http\Requests\Consultations;

use App\Enums\Consultations\AllocationFallback;
use Illuminate\Validation\Rule;

final class UpdateAllocationFallbackRequest extends AuthorizedConsultationRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'fallback_channel' => ['required', Rule::enum(AllocationFallback::class)],
        ];
    }

    public function fallbackChannel(): AllocationFallback
    {
        return AllocationFallback::from($this->string('fallback_channel')->toString());
    }
}
