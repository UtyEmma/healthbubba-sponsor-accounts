<?php

namespace App\Console\Commands;

use App\Actions\Campaigns\ProcessCampaignLifecycleAction;
use Illuminate\Console\Command;

final class ProcessCampaignLifecycle extends Command
{
    protected $signature = 'campaigns:process-lifecycle';

    protected $description = 'Advance campaign states, reconcile refunds, and bill active booths.';

    public function handle(ProcessCampaignLifecycleAction $process): int
    {
        $count = $process->execute();
        $this->components->info("Processed {$count} campaign lifecycle operation(s).");

        return self::SUCCESS;
    }
}
