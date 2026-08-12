<?php

namespace App\Actions\WorkspaceBeneficiaries;

use App\Mail\WorkspaceBeneficiaryInvitationMail;
use App\Models\WorkspaceBeneficiary;
use App\Services\WorkspaceBeneficiaries\InvitationUrlService;
use Illuminate\Support\Facades\Mail;

final readonly class SendWorkspaceBeneficiaryInvitationAction
{
    public function __construct(private InvitationUrlService $urls) {}

    public function execute(WorkspaceBeneficiary $invitation): void
    {
        $invitation->loadMissing('workspace');

        Mail::to($invitation->email)->send(new WorkspaceBeneficiaryInvitationMail(
            inviteeName: trim("{$invitation->first_name} {$invitation->last_name}"),
            workspaceName: $invitation->workspace->name,
            invitationUrl: $this->urls->page($invitation),
            expiresAt: $invitation->expires_at->timezone(config('app.timezone'))->toDayDateTimeString(),
        ));
    }
}
