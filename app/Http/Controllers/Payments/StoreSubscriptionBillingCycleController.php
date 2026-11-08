<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Payments\ScheduleSubscriptionBillingCycleAction;
use App\DTOs\Payments\ScheduleSubscriptionBillingCycleData;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StoreSubscriptionBillingCycleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class StoreSubscriptionBillingCycleController extends Controller
{
    public function __invoke(
        StoreSubscriptionBillingCycleRequest $request,
        ScheduleSubscriptionBillingCycleAction $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        try {
            $subscription = $action->execute(new ScheduleSubscriptionBillingCycleData(
                workspace: $request->workspace(),
                user: $user,
                subscription: $request->subscription(),
                billingCycle: $request->billingCycle(),
            ));
        } catch (CheckoutUnavailable $exception) {
            return back()->withErrors(['billing_cycle' => $exception->getMessage()]);
        }

        return back()->with('success', $subscription->scheduled_billing_cycle === null
            ? 'The scheduled billing interval change was cancelled.'
            : 'Your billing interval will change at the next renewal.');
    }
}
