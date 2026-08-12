<?php

namespace App\Http\Controllers\WorkspaceBeneficiaries;

use App\Actions\WorkspaceBeneficiaries\ExpireWorkspaceBeneficiaryInvitationsAction;
use App\Enums\AccountTypes;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Http\Resources\WorkspaceBeneficiaryResource;
use App\Models\Workspace;
use App\Services\WorkspaceBeneficiaries\WorkspaceBeneficiaryCapacityService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class WorkspaceBeneficiaryIndexController
{
    public function __construct(
        private ExpireWorkspaceBeneficiaryInvitationsAction $expireInvitations,
        private WorkspaceBeneficiaryCapacityService $capacity,
    ) {}

    public function __invoke(Request $request): Response {
        $workspace = Workspace::current();
        abort_if($workspace === null, 404);
        abort_unless(in_array($workspace->type, [AccountTypes::INDIVIDUAL, AccountTypes::BUSINESS], true), 403);

        $this->expireInvitations->execute();
        $summary = $this->capacity->summary($workspace);
        $activeCount = $workspace->workspaceBeneficiaries()
            ->where('status', WorkspaceBeneficiaryStatus::Active)
            ->count();
        $pendingCount = $workspace->workspaceBeneficiaries()
            ->where('status', WorkspaceBeneficiaryStatus::Pending)
            ->count();
        $invitations = $workspace->workspaceBeneficiaries()
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render(
            $workspace->type === AccountTypes::BUSINESS
                ? 'business-sponsor/employees/index'
                : 'sponsor/beneficiaries/index',
            [
                'invitations' => WorkspaceBeneficiaryResource::collection($invitations),
                'capacity' => [
                    'used' => $summary->used,
                    'total' => $summary->total,
                    'remaining' => $summary->remaining(),
                    'canInvite' => $summary->canInvite(),
                    'unavailableReason' => $summary->unavailableReason,
                ],
                'counts' => [
                    'active' => $activeCount,
                    'pending' => $pendingCount,
                ],
                'importResult' => $request->session()->get('import_result'),
            ],
        );
    }
}
