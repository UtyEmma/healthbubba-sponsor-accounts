<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Payments\StartWalletFundingAction;
use App\DTOs\Payments\StartWalletFundingData;
use App\Exceptions\Payments\PaymentException;
use App\Exceptions\Payments\PaymentVerificationFailed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StoreWalletPaymentRequest;
use App\Models\User;
use App\ValueObjects\Money;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class StoreWalletPaymentController extends Controller
{
    public function store(
        StoreWalletPaymentRequest $request,
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
                gateway: $request->gateway(),
            ));
        } catch (PaymentException|PaymentVerificationFailed $exception) {
            report($exception);

            

            return back()->withErrors([
                'payment' => 'Unable to start the payment right now. Please try again.',
            ]);
        }

        return Inertia::location($session->authorizationUrl);
    }
}
