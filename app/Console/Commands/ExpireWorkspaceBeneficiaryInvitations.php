<?php

namespace App\Console\Commands;

use App\Actions\WorkspaceBeneficiaries\ExpireWorkspaceBeneficiaryInvitationsAction;
use Illuminate\Console\Command;

final class ExpireWorkspaceBeneficiaryInvitations extends Command
{
    protected $signature = 'workspace-beneficiaries:expire-invitations';

    protected $description = 'Expire pending workspace beneficiary invitations whose signed links have elapsed';

    public function handle(ExpireWorkspaceBeneficiaryInvitationsAction $expire): int
    {
        $count = $expire->execute();
        $this->components->info("Expired {$count} workspace invitation(s).");

        return self::SUCCESS;
    }
}
