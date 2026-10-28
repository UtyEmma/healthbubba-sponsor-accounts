<?php

namespace App\Enums;

enum CampaignRecurringCostCategory: string
{
    case BoothService = 'booth_service';
    case CampaignService = 'campaign_service';
    case Operating = 'operating';
}
