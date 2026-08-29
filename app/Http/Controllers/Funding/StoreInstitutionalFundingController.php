<?php

namespace App\Http\Controllers\Funding;

use App\Actions\Payments\StartWalletFundingAction;
use App\DTOs\Payments\StartWalletFundingData;
use App\Enums\Payments\PaymentGatewayName;
use App\Exceptions\Payments\PaymentException;
use App\Exceptions\Payments\PaymentVerificationFailed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Funding\StoreInstitutionalFundingRequest;
use App\Models\User;
use App\ValueObjects\Money;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class StoreInstitutionalFundingController extends Controller
{
    public function __invoke(
        StoreInstitutionalFundingRequest $request,
        StartWalletFundingAction $action,
    ): Response {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        try {
            $session = $action->execute(new StartWalletFundingData(
                workspace: $request->workspace(),
                user: $user,
                amount: Money::fromMajor($request->amount(), config()->string('payments.currency', 'NGN')),
                callbackUrl: route('payments.callback'),
                gateway: PaymentGatewayName::PAYSTACK,
                channels: ['bank_transfer'],
                fundingMethod: 'bank_transfer',
            ));
        } catch (PaymentException|PaymentVerificationFailed $exception) {
            report($exception);

            return back()->withErrors([
                'funding_payment' => 'Unable to start the bank transfer right now. Please try again.',
            ]);
        }

        return Inertia::location($session->authorizationUrl);
    }
}
