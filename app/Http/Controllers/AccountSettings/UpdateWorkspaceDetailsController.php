<?php

namespace App\Http\Controllers\AccountSettings;

use App\Http\Requests\AccountSettings\UpdateWorkspaceDetailsRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateWorkspaceDetailsController
{
    public function __invoke(UpdateWorkspaceDetailsRequest $request): RedirectResponse
    {
        $request->workspace()->update($request->safe()->only(['name', 'description']));

        return back()->with('success', 'Workspace details updated successfully.');
    }
}
