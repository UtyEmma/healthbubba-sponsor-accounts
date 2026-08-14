<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Requests\Consultations\UpdateAllocationFallbackRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateAllocationFallbackController
{
    public function __invoke(UpdateAllocationFallbackRequest $request): RedirectResponse
    {
        $request->workspace()->update([
            'fallback_channel' => $request->fallbackChannel(),
        ]);

        return back()->with('success', 'Allocation fallback channel updated successfully.');
    }
}
