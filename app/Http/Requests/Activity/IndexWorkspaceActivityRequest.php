<?php

namespace App\Http\Requests\Activity;

final class IndexWorkspaceActivityRequest extends AuthorizedWorkspaceActivityRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
