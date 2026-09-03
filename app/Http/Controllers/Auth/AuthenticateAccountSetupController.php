<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateAccountSetupAction;
use App\Http\Requests\Auth\AuthenticateAccountSetupRequest;
use App\Http\Requests\Auth\StoreOwnedWorkspaceRequest;
use App\Http\Resources\Auth\AccountSetupAuthenticationResource;
use Illuminate\Contracts\Auth\StatefulGuard;

final readonly class AuthenticateAccountSetupController
{
    public function __construct(
        private AuthenticateAccountSetupAction $authenticateSetup,
        private StatefulGuard $guard,
    ) {}

    public function __invoke(AuthenticateAccountSetupRequest $request): AccountSetupAuthenticationResource
    {
        $authentication = $this->authenticateSetup->execute(
            $request->email(),
            $request->password(),
            $request->accountType(),
        );

        $this->guard->login($authentication->user);
        $request->session()->regenerate();
        $request->session()->put(StoreOwnedWorkspaceRequest::SESSION_KEY, [
            'user_id' => $authentication->user->getKey(),
            'account_type' => $request->accountType()->value,
            'verified_at' => now()->timestamp,
        ]);

        if ($authentication->activeMembership !== null) {
            $authentication->activeMembership->update(['last_selected_at' => now()]);
            $request->session()->put('current_workspace_id', $authentication->activeMembership->workspace_id);
        } else {
            $request->session()->forget('current_workspace_id');
        }

        return new AccountSetupAuthenticationResource($authentication);
    }
}
