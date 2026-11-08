<?php

namespace App\Http\Controllers;

use App\Exceptions\Payments\CheckoutUnavailable;
use App\Http\Resources\BillingWalletResource;
use App\Http\Resources\CapacityPurchaseSummaryResource;
use App\Http\Resources\FrequentlyAskedQuestionResource;
use App\Http\Resources\PlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Mappers\WorkspacePlanMapper;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Workspace;
use App\Queries\Billing\GetFrequentlyAskedQuestions;
use App\Services\Payments\CapacityPricingService;
use App\Services\Payments\IndividualPlanIntervalPricingService;
use App\Services\Payments\PlanPricingService;
use App\Services\WorkspaceMembers\WorkspaceMemberAccessService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BillingController extends Controller
{
    public function __construct(
        private readonly WorkspacePlanMapper $workspacePlans,
        private readonly CapacityPricingService $capacityPricing,
        private readonly PlanPricingService $planPricing,
        private readonly WorkspaceMemberAccessService $workspaceAccess,
        private readonly GetFrequentlyAskedQuestions $frequentlyAskedQuestions,
        private readonly IndividualPlanIntervalPricingService $intervalPricing,
    ) {}

    public function __invoke(Request $request): Response
    {
        $workspace = Workspace::current();

        abort_if($workspace === null, 404);
        abort_unless($request->user() instanceof User && $this->workspaceAccess->canManage($request->user(), $workspace), 403);

        $subscription = Subscription::query()
            ->with(['plan.features', 'scheduledPlan.features'])
            ->where('subscribable_type', $workspace->getMorphClass())
            ->where('subscribable_id', $workspace->getKey())
            ->latest('id')
            ->first();
        $wallet = $workspace->wallet()->first() ?? new Wallet([
            'balance' => '0.00',
            'currency' => config()->string('payments.currency', 'NGN'),
        ]);

        return Inertia::render('billing/index', [
            'accountType' => $workspace->type->value,
            'accountTypeLabel' => $workspace->type->label(),
            'plans' => PlanResource::collection(
                $this->workspacePlans->mapAvailable($workspace, $subscription),
            ),
            'wallet' => new BillingWalletResource($wallet),
            'subscription' => $subscription === null
                ? null
                : new SubscriptionResource(
                    resource: $subscription,
                    renewalAmount: $this->renewalAmount($subscription),
                ),
            'capacityPurchase' => $subscription === null
                ? null
                : $this->capacityPurchaseSummary($subscription, $workspace, $wallet),
            'faqs' => FrequentlyAskedQuestionResource::collection(
                $this->frequentlyAskedQuestions->execute($workspace->type),
            ),
        ]);
    }

    private function renewalAmount(Subscription $subscription): string
    {
        try {
            $renewalPlan = $subscription->scheduledPlan ?? $subscription->plan;

            return $this->planPricing
                ->renewalForPlan(
                    $subscription,
                    $renewalPlan,
                    $this->intervalPricing->renewalCycle($subscription),
                )
                ->money
                ->toMajorAmount();
        } catch (CheckoutUnavailable) {
            return $subscription->plan->price;
        }
    }

    private function capacityPurchaseSummary(
        Subscription $subscription,
        Workspace $workspace,
        Wallet $wallet,
    ): ?CapacityPurchaseSummaryResource {
        $summary = $this->capacityPricing->summary(
            subscription: $subscription,
            wallet: $wallet,
        );

        return $summary === null ? null : new CapacityPurchaseSummaryResource($summary);
    }
}
