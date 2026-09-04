<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Payments\StartSubscriptionRenewalAction;
use App\DTOs\Payments\StartSubscriptionRenewalData;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Exceptions\Payments\PaymentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StoreSubscriptionRenewalRequest;
use App\Models\Subscription;
use App\Models\User;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class StoreSubscriptionRenewalController extends Controller
{
    public function __invoke(
        StoreSubscriptionRenewalRequest $request,
        Subscription $subscription,
        StartSubscriptionRenewalAction $action,
    ): Response {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        try {
            $result = $action->execute(new StartSubscriptionRenewalData(
                workspace: $request->workspace(),
                user: $user,
                subscription: $request->subscription(),
                paymentSource: $request->paymentSource(),
                callbackUrl: route('payments.callback'),
            ));
        } catch (CheckoutUnavailable $exception) {
            return back()->withErrors(['renewal' => $exception->getMessage()]);
        } catch (PaymentException $exception) {
            report($exception);

            return back()->withErrors(['renewal' => 'Unable to start the renewal payment right now.']);
        }

        if ($result->checkoutSession !== null) {
            return Inertia::location($result->checkoutSession->authorizationUrl);
        }

        return back()->with('success', 'Your subscription has been renewed.');
    }
}
