<?php

namespace App\Http\Controllers\WorkspaceBeneficiaries;

use App\Actions\WorkspaceBeneficiaries\DecideWorkspaceBeneficiaryInvitationAction;
use App\Http\Requests\WorkspaceBeneficiaries\DecideWorkspaceBeneficiaryRequest;
use App\Models\WorkspaceBeneficiary;
use App\Services\HealthBubba\HealthBubbaWebUrlService;
use App\Services\WorkspaceBeneficiaries\InvitationUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

final readonly class DecideWorkspaceBeneficiaryInvitationController
{
    public function __construct(
        private DecideWorkspaceBeneficiaryInvitationAction $decide,
        private InvitationUrlService $urls,
        private HealthBubbaWebUrlService $healthBubbaWebUrls,
    ) {}

    public function __invoke(
        DecideWorkspaceBeneficiaryRequest $request,
        WorkspaceBeneficiary $workspaceBeneficiary,
    ): RedirectResponse {
        abort_unless(URL::hasCorrectSignature($request), 403);
        abort_unless((int) $request->query('version') === $workspaceBeneficiary->invitation_version, 403);

        if ($workspaceBeneficiary->hasExpired()) {
            $invitation = $this->decide->execute($workspaceBeneficiary, $request->string('decision')->toString());

            return redirect()->to($this->urls->page($invitation));
        }

        if (! $workspaceBeneficiary->isPending()) {
            return redirect()->to($this->urls->page($workspaceBeneficiary));
        }

        abort_unless($request->hasValidSignature(), 403);
        $decision = $request->string('decision')->toString();
        $invitation = $this->decide->execute($workspaceBeneficiary, $decision);

        if ($decision === 'accept' && $invitation->isActive()) {
            return redirect()->away(
                $this->healthBubbaWebUrls->patientAuthentication($invitation->beneficiary_id !== null),
            );
        }

        return redirect()->to($this->urls->page($invitation));
    }
}
