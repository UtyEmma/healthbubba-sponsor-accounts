<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\Actions\CampaignAdminActions;
use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCampaign extends ViewRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CampaignAdminActions::pause(),
            CampaignAdminActions::resume(),
            CampaignAdminActions::runMonthlyDeductions(),
            CampaignAdminActions::deactivateBooths(),
            CampaignAdminActions::end(),
            EditAction::make(),
        ];
    }
}
