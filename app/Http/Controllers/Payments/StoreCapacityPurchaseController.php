<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Payments\StartCapacityPurchaseAction;
use App\DTOs\CapacityPurchases\StartCapacityPurchaseData;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Exceptions\Payments\PaymentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StoreCapacityPurchaseRequest;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class StoreCapacityPurchaseController extends Controller
{
    public function store(
        StoreCapacityPurchaseRequest $request,
        Subscription $subscription,
        StartCapacityPurchaseAction $action,
    ): Response {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        try {
            $result = $action->execute(new StartCapacityPurchaseData(
                workspace: $request->workspace(),
                user: $user,
                subscription: $request->subscription(),
                quantity: $request->quantity(),
                paymentSource: $request->paymentSource(),
                callbackUrl: route('payments.callback'),
            ));
        } catch (CheckoutUnavailable $exception) {
            return back()->withErrors(['capacity' => $exception->getMessage()]);
        } catch (PaymentException $exception) {
            report($exception);

            return back()->withErrors([
                'capacity' => 'Unable to start the payment right now. Please try again.',
            ]);
        }

        if ($result->checkoutSession !== null) {
            return Inertia::location($result->checkoutSession->authorizationUrl);
        }

        /** @var RedirectResponse */
        return back()->with('success', 'Your additional capacity is now available.');
    }
}
