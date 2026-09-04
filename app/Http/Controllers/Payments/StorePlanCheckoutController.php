<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Payments\StartPlanCheckoutAction;
use App\DTOs\Payments\StartPlanCheckoutData;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Exceptions\Payments\PaymentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StorePlanCheckoutRequest;
use App\Models\Plan;
use App\Models\User;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class StorePlanCheckoutController extends Controller
{
    public function store(
        StorePlanCheckoutRequest $request,
        Plan $plan,
        StartPlanCheckoutAction $action,
    ): Response {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        try {
            $session = $action->execute(new StartPlanCheckoutData(
                workspace: $request->workspace(),
                user: $user,
                plan: $request->plan(),
                additionalCapacity: $request->additionalCapacity(),
                callbackUrl: route('payments.callback'),
                paymentSource: $request->paymentSource(),
                gateway: $request->gateway(),
            ));
        } catch (CheckoutUnavailable $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        } catch (PaymentException $exception) {
            report($exception);

            return back()->withErrors([
                'payment' => 'Unable to start the payment right now. Please try again.',
            ]);
        }

        if ($session->checkoutSession !== null) {
            return Inertia::location($session->checkoutSession->authorizationUrl);
        }

        return back()->with('success', 'Your subscription is now active.');
    }
}
