<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Payments\StartPlanChangeAction;
use App\DTOs\Payments\StartPlanChangeData;
use App\Enums\Subscriptions\PlanChangeDirection;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Exceptions\Payments\PaymentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StorePlanChangeRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class StorePlanChangeController extends Controller
{
    public function store(
        StorePlanChangeRequest $request,
        Subscription $subscription,
        Plan $plan,
        StartPlanChangeAction $action,
    ): Response {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        try {
            $result = $action->execute(new StartPlanChangeData(
                workspace: $request->workspace(),
                user: $user,
                subscription: $request->subscription(),
                targetPlan: $request->targetPlan(),
                callbackUrl: route('payments.callback'),
            ));
        } catch (CheckoutUnavailable $exception) {
            return back()->withErrors(['plan_change' => $exception->getMessage()]);
        } catch (PaymentException $exception) {
            report($exception);

            return back()->withErrors([
                'plan_change' => 'Unable to start the plan upgrade payment right now. Please try again.',
            ]);
        }

        if ($result->checkoutSession !== null) {
            return Inertia::location($result->checkoutSession->authorizationUrl);
        }

        $message = $result->quote->direction === PlanChangeDirection::DOWNGRADE
            ? 'Your downgrade is scheduled for the next billing cycle.'
            : 'Your plan has been upgraded.';

        return back()->with('success', $message);
    }
}
