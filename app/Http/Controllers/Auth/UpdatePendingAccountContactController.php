<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AccountVerification\UpdatePendingAccountContactAction;
use App\Http\Requests\Auth\UpdatePendingAccountContactRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final readonly class UpdatePendingAccountContactController
{
    public function __construct(private UpdatePendingAccountContactAction $updateContact) {}

    public function __invoke(UpdatePendingAccountContactRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->updateContact->execute(
            user: $user,
            email: (string) $request->validated('email'),
            phone: (string) $request->validated('phone'),
        );

        return redirect()->route('account_verification.show');
    }
}
