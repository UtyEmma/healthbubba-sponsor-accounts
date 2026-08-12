<?php

namespace App\Console\Commands;

use App\Actions\MedicalAccess\ExpireMedicalAccessRequestsAction;
use Illuminate\Console\Command;

final class ExpireMedicalAccessRequests extends Command
{
    protected $signature = 'medical-access:expire';

    protected $description = 'Expire elapsed medical consent requests and approved access grants';

    public function handle(ExpireMedicalAccessRequestsAction $expireRequests): int
    {
        $count = $expireRequests->execute();
        $this->components->info("Expired {$count} medical access request(s).");

        return self::SUCCESS;
    }
}
