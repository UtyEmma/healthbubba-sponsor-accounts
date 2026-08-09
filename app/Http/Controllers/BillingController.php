<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlanResource;
use App\Mappers\WorkspacePlanMapper;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BillingController extends Controller
{
    public function __construct(
        private readonly WorkspacePlanMapper $workspacePlans,
    ) {}

    public function __invoke(Request $request): Response
    {
        $workspace = Workspace::current();

        abort_if($workspace === null, 404);

        return Inertia::render('billing/index', [
            'plans' => PlanResource::collection(
                $this->workspacePlans->mapAvailable($workspace),
            ),
        ]);
    }
}
