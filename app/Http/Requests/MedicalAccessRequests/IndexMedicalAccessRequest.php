<?php

namespace App\Http\Requests\MedicalAccessRequests;

final class IndexMedicalAccessRequest extends AuthorizedMedicalAccessRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
