<?php

namespace App\Filament\Resources\CampaignPricingTiers\Pages;

use App\Filament\Resources\CampaignPricingTiers\CampaignPricingTierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCampaignPricingTiers extends ListRecords
{
    protected static string $resource = CampaignPricingTierResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
