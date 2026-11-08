<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Payments\StartBillingCycleUpgradeAction;
use App\DTOs\Payments\StartBillingCycleUpgradeData;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Exceptions\Payments\PaymentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StoreSubscriptionBillingCycleRequest;
use App\Models\User;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class StoreSubscriptionBillingCycleController extends Controller
{
    public function __invoke(
        StoreSubscriptionBillingCycleRequest $request,
        StartBillingCycleUpgradeAction $action,
    ): Response {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        try {
            $result = $action->execute(new StartBillingCycleUpgradeData(
                workspace: $request->workspace(),
                user: $user,
                subscription: $request->subscription(),
                targetCycle: $request->billingCycle(),
                callbackUrl: route('payments.callback'),
                paymentSource: $request->paymentSource(),
            ));
        } catch (CheckoutUnavailable $exception) {
            return back()->withErrors(['billing_cycle' => $exception->getMessage()]);
        } catch (PaymentException $exception) {
            report($exception);

            return back()->withErrors([
                'billing_cycle' => 'Unable to start the billing-cycle upgrade payment right now. Please try again.',
            ]);
        }

        if ($result->checkoutSession !== null) {
            return Inertia::location($result->checkoutSession->authorizationUrl);
        }

        return back()->with('success', 'Your billing cycle has been upgraded and the new period is now active.');
    }
}
