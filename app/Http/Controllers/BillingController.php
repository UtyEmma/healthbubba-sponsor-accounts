<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlanResource;
use App\Models\User;
use App\Models\Workspace;
use App\Queries\Plans\GetPlanBillingData;
use App\Repositories\PlansRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BillingController extends Controller {

    function __construct(private PlansRepository $plansRepository) {
    }

    public function __invoke(Request $request): Response {
        $plans = $this->plansRepository->getWorkspacePlans();

        return Inertia::render('billing/index', [
            'plans' => PlanResource::collection($plans)
        ]);
    }
}
