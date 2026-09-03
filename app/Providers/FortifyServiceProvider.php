<?php

namespace App\Providers;

use App\Actions\Auth\AuthenticateWorkspaceLoginAction;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Enums\AccountTypes;
use App\Enums\InstitutionalOrganizationType;
use App\Enums\NigeriaState;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(AuthenticateWorkspaceLoginAction $authenticateWorkspace): void
    {
        $this->configureActions($authenticateWorkspace);
        $this->configureViews();
    }

    public function configureActions(AuthenticateWorkspaceLoginAction $authenticateWorkspace): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);
        Fortify::authenticateUsing(function (Request $request) use ($authenticateWorkspace) {
            $accountType = AccountTypes::tryFrom((string) $request->input('account_type'));

            if ($accountType === null) {
                throw ValidationException::withMessages([
                    'account_type' => 'Select the account type you want to sign in to.',
                ]);
            }

            $authentication = $authenticateWorkspace->execute(
                (string) $request->input('email'),
                (string) $request->input('password'),
                $accountType,
            );

            if ($authentication === null) {
                return null;
            }

            $request->session()->put('current_workspace_id', $authentication->membership->workspace_id);

            return $authentication->user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower((string) $request->input(Fortify::username()))
                .'|'.$request->input('account_type')
                .'|'.$request->ip(),
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('account-availability', function (Request $request) {
            return Limit::perMinute(10)->by(
                Str::lower(trim((string) $request->input('email')))
                .'|'.$request->input('account_type')
                .'|'.$request->ip(),
            );
        });

        RateLimiter::for('account-setup', function (Request $request) {
            $email = (string) ($request->input('email') ?: $request->user()?->email);

            return Limit::perMinute(5)->by(
                Str::lower(trim($email))
                .'|'.$request->input('account_type')
                .'|'.$request->ip(),
            );
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }

    public function configureViews(): void
    {
        Fortify::registerView(fn (Request $request) => Inertia::render('auth/register', [
            'organizationTypes' => InstitutionalOrganizationType::options(),
            'countries' => [
                ['value' => 'NG', 'label' => 'Nigeria'],
            ],
            'states' => NigeriaState::options(),
            'initialAccountType' => $this->requestedAccountType($request),
            'initialEmail' => Str::lower(trim((string) $request->query('email'))),
        ]));
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'initialAccountType' => $this->requestedAccountType($request),
            'initialEmail' => Str::lower(trim((string) $request->query('email'))),
        ]));
    }

    private function requestedAccountType(Request $request): ?string
    {
        return AccountTypes::tryFrom((string) $request->query('account_type'))?->value;
    }
}
