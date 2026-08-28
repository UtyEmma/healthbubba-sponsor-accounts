<?php

namespace App\Http\Controllers\Auth;

use App\Actions\InstitutionalRegistration\CreateInstitutionalSponsorAccountAction;
use App\Http\Requests\Auth\StoreInstitutionalSponsorRegistrationRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;

final readonly class StoreInstitutionalSponsorRegistrationController
{
    public function __construct(
        private CreateInstitutionalSponsorAccountAction $createAccount,
        private StatefulGuard $guard,
    ) {}

    public function __invoke(StoreInstitutionalSponsorRegistrationRequest $request): RedirectResponse
    {
        $account = $this->createAccount->execute($request->registrationData());

        event(new Registered($account->user));
        $this->guard->login($account->user);
        $request->session()->regenerate();
        $request->session()->put('current_workspace_id', $account->workspace->getKey());

        return redirect()->route('account_verification.show');
    }
}
