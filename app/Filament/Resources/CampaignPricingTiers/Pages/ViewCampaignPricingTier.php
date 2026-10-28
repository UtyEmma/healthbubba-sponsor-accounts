<?php

namespace App\Filament\Resources\CampaignPricingTiers\Pages;

use App\Filament\Resources\CampaignPricingTiers\CampaignPricingTierResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewCampaignPricingTier extends ViewRecord
{
    protected static string $resource = CampaignPricingTierResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
