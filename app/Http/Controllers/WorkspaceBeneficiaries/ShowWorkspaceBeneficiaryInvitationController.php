<?php

namespace App\Http\Controllers\WorkspaceBeneficiaries;

use App\Actions\WorkspaceBeneficiaries\DecideWorkspaceBeneficiaryInvitationAction;
use App\Models\WorkspaceBeneficiary;
use App\Services\WorkspaceBeneficiaries\InvitationUrlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowWorkspaceBeneficiaryInvitationController
{
    public function __construct(
        private DecideWorkspaceBeneficiaryInvitationAction $decide,
        private InvitationUrlService $urls,
    ) {}

    public function __invoke(Request $request, WorkspaceBeneficiary $workspaceBeneficiary): Response
    {
        abort_unless(URL::hasCorrectSignature($request), 403);
        abort_unless((int) $request->query('version') === $workspaceBeneficiary->invitation_version, 403);

        if ($workspaceBeneficiary->hasExpired()) {
            $workspaceBeneficiary = $this->decide->execute($workspaceBeneficiary, 'decline');
        } elseif ($workspaceBeneficiary->isPending()) {
            abort_unless($request->hasValidSignature(), 403);
        }

        $workspaceBeneficiary->loadMissing('workspace');

        return Inertia::render('invitations/workspace-beneficiary', [
            'invitation' => [
                'name' => trim("{$workspaceBeneficiary->first_name} {$workspaceBeneficiary->last_name}"),
                'workspaceName' => $workspaceBeneficiary->workspace->name,
                'status' => $workspaceBeneficiary->status->value,
                'expiresAt' => $workspaceBeneficiary->expires_at->toISOString(),
            ],
            'decisionUrl' => $workspaceBeneficiary->isPending()
                ? $this->urls->decision($workspaceBeneficiary)
                : null,
        ]);
    }
}
