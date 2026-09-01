<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignBoothStatus;
use App\Models\CampaignBooth;
use Illuminate\Support\Facades\DB;

final class DeactivateCampaignBoothRecordAction
{
    public function execute(CampaignBooth $booth): CampaignBooth
    {
        return DB::transaction(function () use ($booth): CampaignBooth {
            $booth = CampaignBooth::query()->whereKey($booth->getKey())->lockForUpdate()->firstOrFail();
            $now = now();
            $booth->update([
                'status' => CampaignBoothStatus::Inactive,
                'deactivated_at' => $now,
                'billing_grace_ends_on' => null,
            ]);
            $booth->recurringCosts()->where('is_active', true)->update([
                'is_active' => false,
                'ends_on' => today()->toDateString(),
                'deactivated_at' => $now,
                'updated_at' => $now,
            ]);

            return $booth->refresh();
        }, 3);
    }
}
