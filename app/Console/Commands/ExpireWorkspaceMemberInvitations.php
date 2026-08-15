<?php

namespace App\Console\Commands;

use App\Actions\WorkspaceMembers\ExpireWorkspaceMemberInvitationsAction;
use Illuminate\Console\Command;

final class ExpireWorkspaceMemberInvitations extends Command
{
    protected $signature = 'workspace-members:expire-invitations';

    protected $description = 'Expire stale workspace team invitations';

    public function handle(ExpireWorkspaceMemberInvitationsAction $expire): int
    {
        $this->info("Expired {$expire->execute()} workspace team invitation(s).");

        return self::SUCCESS;
    }
}
