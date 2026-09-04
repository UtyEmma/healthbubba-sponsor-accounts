<?php

namespace App\Console\Commands;

use App\Actions\Payments\ReconcileScheduledDowngradesAction;
use Illuminate\Console\Command;

final class ReconcileScheduledDowngrades extends Command
{
    protected $signature = 'subscriptions:reconcile-scheduled-downgrades';

    protected $description = 'Apply eligible legacy downgrades immediately and cancel blocked schedules';

    public function handle(ReconcileScheduledDowngradesAction $action): int
    {
        $result = $action->execute();

        $this->info("Applied {$result['applied']} scheduled downgrade(s).");
        $this->info("Cancelled {$result['cancelled']} blocked schedule(s).");

        return self::SUCCESS;
    }
}
