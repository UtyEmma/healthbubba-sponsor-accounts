<?php

namespace App\Console\Commands;

use App\Actions\Payments\DispatchDueSubscriptionRenewalsAction;
use Illuminate\Console\Command;

final class DispatchSubscriptionRenewals extends Command
{
    protected $signature = 'subscriptions:dispatch-renewals';

    protected $description = 'Dispatch due recurring subscription charges';

    public function handle(DispatchDueSubscriptionRenewalsAction $action): int
    {
        $action->execute();

        return self::SUCCESS;
    }
}
