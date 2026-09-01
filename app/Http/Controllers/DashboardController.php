<?php

namespace App\Http\Controllers;

use App\Enums\AccountTypes;
use App\Http\Resources\InstitutionalDashboardResource;
use App\Http\Resources\WorkspaceDashboardResource;
use App\Models\User;
use App\Models\Workspace;
use App\Queries\Dashboard\InstitutionalDashboardQuery;
use App\Queries\Dashboard\WorkspaceDashboardQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function __construct(
        private WorkspaceDashboardQuery $dashboard,
        private InstitutionalDashboardQuery $institutionalDashboard,
    ) {}

    public function __invoke(Request $request): Response
    {
        $workspace = Workspace::current();
        $user = $request->user();

        abort_unless($user instanceof User, 404);

        if (! $workspace instanceof Workspace) {
            return Inertia::render('workspace-access');
        }

        $page = match ($workspace->type) {
            AccountTypes::INDIVIDUAL => 'sponsor/dashboard',
            AccountTypes::BUSINESS => 'business-sponsor/dashboard',
            AccountTypes::INSTITUTION => 'institutional-sponsor/dashboard',
        };

        if ($workspace->type === AccountTypes::INSTITUTION) {
            return Inertia::render($page, [
                'dashboard' => new InstitutionalDashboardResource(
                    $this->institutionalDashboard->execute($workspace, $user),
                ),
            ]);
        }

        return Inertia::render($page, [
            'dashboard' => new WorkspaceDashboardResource(
                $this->dashboard->execute($workspace, $user),
            ),
        ]);
    }
}
