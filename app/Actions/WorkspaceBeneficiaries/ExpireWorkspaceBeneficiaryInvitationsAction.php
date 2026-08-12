<?php

namespace App\Actions\WorkspaceBeneficiaries;

use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\WorkspaceBeneficiary;

final class ExpireWorkspaceBeneficiaryInvitationsAction
{
    public function execute(): int
    {
        return WorkspaceBeneficiary::query()
            ->where('status', WorkspaceBeneficiaryStatus::Pending)
            ->where('expires_at', '<=', now())
            ->update(['status' => WorkspaceBeneficiaryStatus::Expired]);
    }
}
