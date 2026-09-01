<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignBoothStatus;
use App\Models\CampaignBooth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ActivateCampaignBoothRecordAction
{
    public function execute(CampaignBooth $booth): CampaignBooth
    {
        return DB::transaction(function () use ($booth): CampaignBooth {
            $booth = CampaignBooth::query()
                ->whereKey($booth->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $campaign = $booth->campaign()->lockForUpdate()->firstOrFail();

            if ($campaign->ended_at !== null || $booth->status === CampaignBoothStatus::Inactive) {
                throw ValidationException::withMessages([
                    'booth' => 'This booth cannot be activated.',
                ]);
            }

            $now = now();
            $booth->update([
                'status' => CampaignBoothStatus::Active,
                'activated_at' => $booth->activated_at ?? $now,
                'deactivated_at' => null,
                'billing_grace_ends_on' => null,
                'billing_suspended_at' => null,
            ]);
            $booth->recurringCosts()->update([
                'is_active' => true,
                'starts_on' => today()->toDateString(),
                'next_charge_on' => today()->toDateString(),
                'ends_on' => null,
                'deactivated_at' => null,
                'updated_at' => $now,
            ]);

            if ($campaign->booth_activated_at === null) {
                $campaign->update(['booth_activated_at' => $now]);
            }

            return $booth->refresh();
        }, 3);
    }
}
