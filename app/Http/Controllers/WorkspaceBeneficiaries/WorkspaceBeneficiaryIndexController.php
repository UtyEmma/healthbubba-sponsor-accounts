<?php

namespace App\Http\Controllers\WorkspaceBeneficiaries;

use App\Actions\WorkspaceBeneficiaries\ExpireWorkspaceBeneficiaryInvitationsAction;
use App\Enums\AccountTypes;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Http\Requests\WorkspaceMembers\AuthorizedWorkspaceViewRequest;
use App\Http\Resources\WorkspaceBeneficiaryResource;
use App\Services\WorkspaceBeneficiaries\WorkspaceBeneficiaryCapacityService;
use Inertia\Inertia;
use Inertia\Response;

final readonly class WorkspaceBeneficiaryIndexController
{
    public function __construct(
        private ExpireWorkspaceBeneficiaryInvitationsAction $expireInvitations,
        private WorkspaceBeneficiaryCapacityService $capacity,
    ) {}

    public function __invoke(AuthorizedWorkspaceViewRequest $request): Response
    {
        $workspace = $request->workspace();
        abort_unless(in_array($workspace->type, [AccountTypes::INDIVIDUAL, AccountTypes::BUSINESS], true), 403);

        $this->expireInvitations->execute();
        $summary = $this->capacity->summary($workspace);
        $activeCount = $workspace->beneficiaryEnrollments()
            ->where('status', WorkspaceBeneficiaryStatus::Active)
            ->count();
        $pendingCount = $workspace->beneficiaryEnrollments()
            ->where('status', WorkspaceBeneficiaryStatus::Pending)
            ->count();
        $invitations = $workspace->beneficiaryEnrollments()
            ->with('relatable')
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
