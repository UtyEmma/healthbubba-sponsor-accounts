<?php

namespace App\Services\WorkspaceBeneficiaries;

use App\Models\WorkspaceBeneficiary;
use Illuminate\Support\Facades\URL;

final class InvitationUrlService
{
    public function page(WorkspaceBeneficiary $invitation): string
    {
        return URL::temporarySignedRoute(
            'workspace-beneficiary-invitations.show',
            $invitation->expires_at,
            [
                'workspaceBeneficiary' => $invitation->public_id,
                'version' => $invitation->invitation_version,
            ],
        );
    }

    public function decision(WorkspaceBeneficiary $invitation): string
    {
        return URL::temporarySignedRoute(
            'workspace-beneficiary-invitations.decide',
            $invitation->expires_at,
            [
                'workspaceBeneficiary' => $invitation->public_id,
                'version' => $invitation->invitation_version,
            ],
        );
    }
}
