<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use App\Models\Campaign;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function afterCreate(): void
    {
        /** @var Campaign $campaign */
        $campaign = $this->record;

        if ($campaign->workspace->onboarded_at === null) {
            $campaign->workspace->update(['onboarded_at' => now()]);
        }
    }
}
